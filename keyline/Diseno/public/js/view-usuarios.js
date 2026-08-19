const ViewUsuarios = (() => {
  async function render(container) {
    const c = window.KL.constants;
    container.innerHTML = `
      <section class="panel glass">
        <div class="panel-head">
          <div><h3>Equipo del proyecto</h3><p>Gestiona técnicos, supervisores y administradores del sistema.</p></div>
          <button class="btn btn-primary" id="btnNewUser">+ Nuevo usuario</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Región asignada</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr></thead>
            <tbody id="usersBody"></tbody>
          </table>
        </div>
      </section>
    `;
    document.getElementById('btnNewUser').addEventListener('click', () => openUserModal());
    await load();
  }

  async function load() {
    const { users } = await Api.listarUsuarios();
    const body = document.getElementById('usersBody');
    body.innerHTML = users.map((u) => `
      <tr>
        <td><strong>${UI.escapeHtml(u.nombre)}</strong></td>
        <td>${UI.escapeHtml(u.email)}</td>
        <td><span class="badge">${roleLabel(u.role)}</span></td>
        <td>${UI.escapeHtml(u.regionAsignada || '—')}</td>
        <td>${u.activo === false ? '<span class="tag tag-pendiente">Inactivo</span>' : '<span class="tag tag-implementado">Activo</span>'}</td>
        <td>${u.ultimoAcceso ? UI.fmtFechaHora(u.ultimoAcceso) : 'Nunca'}</td>
        <td class="row-actions">
          <button class="btn btn-secondary btn-sm" data-edit="${u.id}">Editar</button>
          <button class="btn btn-danger btn-sm" data-del="${u.id}">Eliminar</button>
        </td>
      </tr>
    `).join('');
    body.querySelectorAll('[data-edit]').forEach((b) => b.addEventListener('click', () => openUserModal(users.find((u) => u.id === b.dataset.edit))));
    body.querySelectorAll('[data-del]').forEach((b) => b.addEventListener('click', () => removeUser(b.dataset.del)));
  }

  function roleLabel(r) { return { tecnico: 'Técnico', supervisor: 'Supervisor', administrador: 'Administrador' }[r] || r; }

  function openUserModal(user) {
    const c = window.KL.constants;
    const editing = !!user;
    UI.openModal(`
      <div class="modal-head"><h3>${editing ? 'Editar usuario' : 'Nuevo usuario'}</h3><button class="modal-close" id="umX">✕</button></div>
      <div class="field"><label>Nombre completo</label><input id="umNombre" value="${editing ? UI.escapeHtml(user.nombre) : ''}" /></div>
      <div class="field"><label>Correo electrónico</label><input id="umEmail" type="email" value="${editing ? UI.escapeHtml(user.email) : ''}" ${editing ? 'disabled' : ''} /></div>
      <div class="field"><label>${editing ? 'Nueva contraseña (opcional)' : 'Contraseña'}</label><input id="umPassword" type="password" placeholder="${editing ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres'}" /></div>
      <div class="field"><label>Rol</label><select id="umRole">${UI.optionsHtml(c.ROLES.map(roleLabel).length ? c.ROLES : [], null)}</select></div>
      <div class="field"><label>Región asignada (para supervisores)</label><select id="umRegion"><option value="">Sin asignar / nacional</option>${UI.optionsHtml(c.DEPARTAMENTOS, editing ? user.regionAsignada : '')}</select></div>
      <div class="field"><label>Teléfono</label><input id="umTelefono" value="${editing ? UI.escapeHtml(user.telefono || '') : ''}" /></div>
      ${editing ? `<div class="field"><label style="display:flex;align-items:center;gap:8px;text-transform:none;font-size:14px;color:var(--text);"><input type="checkbox" id="umActivo" ${user.activo !== false ? 'checked' : ''}/> Usuario activo</label></div>` : ''}
      <div class="modal-actions">
        <button class="btn btn-ghost" id="umCancel">Cancelar</button>
        <button class="btn btn-primary" id="umSave">${editing ? 'Guardar cambios' : 'Crear usuario'}</button>
      </div>
    `);
    // rellenar select de roles con labels correctos
    const roleSelect = document.getElementById('umRole');
    roleSelect.innerHTML = c.ROLES.map((r) => `<option value="${r}" ${editing && user.role === r ? 'selected' : ''}>${roleLabel(r)}</option>`).join('');

    document.getElementById('umX').onclick = UI.closeModal;
    document.getElementById('umCancel').onclick = UI.closeModal;
    document.getElementById('umSave').onclick = async () => {
      const payload = {
        nombre: document.getElementById('umNombre').value.trim(),
        email: document.getElementById('umEmail').value.trim(),
        role: document.getElementById('umRole').value,
        regionAsignada: document.getElementById('umRegion').value,
        telefono: document.getElementById('umTelefono').value.trim(),
      };
      const pass = document.getElementById('umPassword').value;
      if (pass) payload.password = pass;
      if (editing) payload.activo = document.getElementById('umActivo').checked;
      if (!payload.nombre || (!editing && (!payload.email || !pass))) {
        UI.toast('Nombre, correo y contraseña son obligatorios.', 'error'); return;
      }
      try {
        if (editing) await Api.actualizarUsuario(user.id, payload);
        else await Api.crearUsuario(payload);
        UI.closeModal();
        UI.toast(editing ? 'Usuario actualizado.' : 'Usuario creado correctamente.', 'success');
        load();
      } catch (err) {
        UI.toast(err.message || 'No se pudo guardar el usuario.', 'error');
      }
    };
  }

  async function removeUser(id) {
    const ok = await UI.confirmDialog('¿Eliminar este usuario del sistema?', { danger: true });
    if (!ok) return;
    try {
      await Api.eliminarUsuario(id);
      UI.toast('Usuario eliminado.', 'info');
      load();
    } catch (err) {
      UI.toast(err.message || 'No se pudo eliminar.', 'error');
    }
  }

  return { render };
})();
