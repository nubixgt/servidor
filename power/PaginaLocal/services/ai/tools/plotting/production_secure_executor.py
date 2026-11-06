import logging
import os
import subprocess
import sys
import tempfile
import textwrap

logger = logging.getLogger(__name__)

# This module runs arbitrary LLM-generated Python code (see PY_TEMPLATE
# below) with NO import/builtin restrictions -- "Allow all imports - no
# restrictions" was the original, honest comment here, not an oversight.
#
# An import-blocklist/attribute-stripping guard was built and tested here
# (targeting os.system/subprocess.Popen/socket.socket/pickle.load/
# ctypes.WinDLL and similar) and had to be reverted: on this project's
# pinned dependency versions (numpy 1.26, pandas 2.2, this Windows Python),
# just `import pandas` -- with zero user code, nothing malicious -- turned
# out to transitively need pickle (numpy's py3k compat shim), os and socket
# (plotly's own __init__.py reads installed-package metadata via
# importlib.metadata -> email.utils), subprocess (pandas's timezone
# localization shells out to `locale -a`), and ctypes.WinDLL specifically
# (dateutil's Windows timezone backend calls into user32.dll to read the
# registry). Every one of those is indistinguishable, at the import-guard
# level, from the same call a malicious payload would make -- there is no
# clean way to allow "the library's own legitimate use" while blocking "the
# same API called by attacker-controlled code" from inside the interpreter
# itself. Chasing this further would mean re-discovering (and silently
# breaking on) the next dependency's own transitive stdlib usage on every
# version bump, which is a worse security posture than being honest that
# this layer provides none.
#
# The actual protection is the subprocess boundary below (separate process,
# own temp cwd deleted after this call, hard timeout, no shared memory with
# the parent) plus the gate immediately below: this whole path is disabled
# by default and must be explicitly opted into via an environment variable.
# It is NOT reachable from the web app (web_app/server.py always calls with
# plotting_enabled=False); only cli/garmin_ai_coach_cli.py's config file
# (extraction.enable_plotting) can turn plotting on at all, for local,
# single-operator use -- and even then, this env var must also be set.
ALLOW_PLOTTING_CODE_EXECUTION_ENV_VAR = "ALLOW_LLM_PLOTTING_CODE_EXECUTION"

PY_TEMPLATE = r"""
import builtins
import types
import sys

# Allow all imports - no restrictions

# ---- User code
{user_code}

# ---- Extract HTML
import plotly.io as pio
if "fig" not in globals():
    raise RuntimeError("No variable named 'fig' was defined.")
_html = pio.to_html(fig, include_plotlyjs="cdn", full_html=False)
print(_html)
"""


def run_plot_code_get_html(code: str, timeout_s: int = 6):
    if os.getenv(ALLOW_PLOTTING_CODE_EXECUTION_ENV_VAR, "").strip().lower() not in ("1", "true", "yes"):
        logger.warning(
            "Plotting code execution is disabled by default (see this module's "
            "docstring for why it can't be meaningfully sandboxed at the import "
            "level). Set %s=1 to opt in.",
            ALLOW_PLOTTING_CODE_EXECUTION_ENV_VAR,
        )
        return {
            "ok": False,
            "error": (
                "Plotting code execution is disabled. Set "
                f"{ALLOW_PLOTTING_CODE_EXECUTION_ENV_VAR}=1 to enable it."
            ),
        }

    user_code = textwrap.dedent(code).strip()
    program = PY_TEMPLATE.format(user_code=user_code)

    with tempfile.TemporaryDirectory() as tmp:
        runner = os.path.join(tmp, "runner.py")
        with open(runner, "w", encoding="utf-8") as f:
            f.write(program)

        try:
            proc = subprocess.run(
                [sys.executable, runner],
                check=False,
                input=b"",
                capture_output=True,
                timeout=timeout_s,
                cwd=tmp,
                env={"PYTHONWARNINGS": "ignore"},  # keep it quiet
            )

            if proc.returncode != 0:
                error_output = proc.stderr.decode("utf-8", "ignore") or proc.stdout.decode(
                    "utf-8", "ignore"
                )
                logger.error(
                    "Subprocess execution failed with return code %d: %s",
                    proc.returncode,
                    error_output,
                )
                return {"ok": False, "error": error_output}

            html_output = proc.stdout.decode("utf-8")
            if not html_output.strip():
                return {"ok": False, "error": "No HTML output generated"}

            return {"ok": True, "html": html_output}

        except subprocess.TimeoutExpired:
            logger.error("Code execution timed out after %d seconds", timeout_s)
            return {"ok": False, "error": f"Code execution timed out after {timeout_s} seconds"}

        except Exception as e:
            logger.error("Subprocess execution failed: %s", e)
            return {"ok": False, "error": f"Execution failed: {e!s}"}


class ProductionSecureExecutor:

    def __init__(self, timeout_s: int = 6):
        self.timeout_s = timeout_s
        logger.info("Initialized ProductionSecureExecutor with %ss timeout", timeout_s)

    def execute_plotting_code(self, code: str) -> tuple[bool, str, str]:
        logger.info("Executing plotting code in secure subprocess")

        result = run_plot_code_get_html(code, self.timeout_s)

        if result["ok"]:
            logger.info("Plotting code executed successfully")
            return True, result["html"], ""
        else:
            logger.warning("Plotting code failed: %s", result["error"])
            return False, "", result["error"]
