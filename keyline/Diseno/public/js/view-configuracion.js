/* Vista "Configuración": preferencias de la cuenta del usuario actual. */
const ViewConfiguracion = (() => {
  async function render(container) {
    const u = App.currentUser;
    container.innerHTML = `
      <div class="grid2">
        <section class="panel glass">
          <div class="panel-head"><div><h3>Tu cuenta</h3><p>Información asociada a tu usuario en el sistema.</p></div></div>
          <div class="form-grid">
            <div class="field"><label>Nombre</label><div>${UI.escapeHtml(u.nombre)}</div></div>
            <div class="field"><label>Correo</label><div>${UI.escapeHtml(u.email)}</div></div>
            <div class="field"><label>Rol</label><div><span class="badge">${roleLabel(u.role)}</span></div></div>
            <div class="field"><label>Región asignada</label><div>${UI.escapeHtml(u.regionAsignada || 'Nacional / sin asignar')}</div></div>
          </div>
        </section>

        <section class="panel glass">
          <div class="panel-head"><div><h3>Cambiar contraseña</h3><p>Usa una contraseña segura que no compartas con nadie más.</p></div></div>
          <form id="passForm">
            <div class="field"><label>Contraseña actual</label><input type="password" id="passActual" required /></div>
            <div class="field"><label>Nueva contraseña</label><input type="password" id="passNueva" required minlength="6" placeholder="Mínimo 6 caracteres" /></div>
            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
          </form>
        </section>
      </div>

      <section class="panel glass">
        <div class="panel-head"><div><h3>Acerca del sistema</h3><p>KeylineGT · Sistema Nacional de Gestión de Parcelas Keyline · Guatemala</p></div></div>
        <p class="hint">Registra parcelas de diseño keyline, captura variables técnicas de suelo, agua, lluvia, bioindicadores y condiciones físicas del terreno, y visualiza el avance nacional mediante un dashboard ejecutivo.</p>
      </section>
    `;

    document.getElementById('passForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const actual = document.getElementById('passActual').value;
      const nueva = document.getElementById('passNueva').value;
      try {
        await Api.cambiarPassword(actual, nueva);
        UI.toast('Contraseña actualizada correctamente.', 'success');
        e.target.reset();
      } catch (err) {
        UI.toast(err.message || 'No se pudo actualizar la contraseña.', 'error');
      }
    });
  }

  function roleLabel(r) { return { tecnico: 'Técnico de campo', supervisor: 'Supervisor regional', administrador: 'Administrador' }[r] || r; }

  return { render };
})();
