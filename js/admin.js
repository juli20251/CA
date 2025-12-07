// ========================================
// ADMIN PANEL - JAVASCRIPT COMPLETO
// ========================================

console.log('✅ Admin panel cargado');

// =====================
// NAVEGACIÓN
// =====================
function mostrarSeccion(seccion) {
    // Ocultar todas las secciones
    document.querySelectorAll('.admin-section').forEach(s => s.style.display = 'none');
    
    // Remover active de todos los links
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    
    // Mostrar la sección seleccionada
    const seccionId = `seccion${seccion.charAt(0).toUpperCase() + seccion.slice(1)}`;
    const elemento = document.getElementById(seccionId);
    
    if (elemento) {
        elemento.style.display = 'block';
        
        // Activar el link correspondiente
        event.target.closest('.nav-link').classList.add('active');
        
        // Cargar datos según la sección
        switch(seccion) {
            case 'dashboard':
                // Dashboard ya está cargado desde PHP
                break;
            case 'categorias':
                cargarCategorias();
                break;
            case 'nominados':
                cargarNominados();
                cargarCategoriasSelect();
                break;
            case 'configuracion':
                // Configuración ya está cargada
                break;
            case 'resultados':
                actualizarResultados(); // ← ESTO ES IMPORTANTE
                break;
            case 'respuestas':
                cargarCategoriasTextoLibre();
                break;
        }
    }
}

// =====================
// CONFIGURACIÓN
// =====================
async function toggleVotaciones() {
    const checkbox = document.getElementById('toggleVotaciones');
    const estado = checkbox.checked ? 'true' : 'false';
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'toggle_votaciones', estado: estado})
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensajeConfig(data.mensaje, 'exito');
        } else {
            checkbox.checked = !checkbox.checked;
            mostrarMensajeConfig(data.mensaje, 'error');
        }
    } catch (error) {
        checkbox.checked = !checkbox.checked;
        mostrarMensajeConfig('Error al actualizar configuración', 'error');
    }
}

async function toggleTernas() {
    const checkbox = document.getElementById('toggleTernas');
    const estado = checkbox.checked ? 'true' : 'false';
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'toggle_ternas', estado: estado})
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensajeConfig(data.mensaje, 'exito');
        } else {
            checkbox.checked = !checkbox.checked;
            mostrarMensajeConfig(data.mensaje, 'error');
        }
    } catch (error) {
        checkbox.checked = !checkbox.checked;
        mostrarMensajeConfig('Error al actualizar configuración', 'error');
    }
}

// =====================
// FECHA DE REVELACIÓN
// =====================
async function guardarFechaRevelacion() {
    const fechaInput = document.getElementById('fechaRevelacion');
    const fecha = fechaInput.value;
    
    if (!fecha) {
        mostrarMensajeConfig('Por favor selecciona una fecha y hora', 'error');
        return;
    }
    
    // Validar que sea fecha futura
    const fechaSeleccionada = new Date(fecha);
    const ahora = new Date();
    
    if (fechaSeleccionada <= ahora) {
        mostrarMensajeConfig('La fecha debe ser futura', 'error');
        return;
    }
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                accion: 'guardar_fecha_revelacion',
                fecha: fecha
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensajeConfig(data.mensaje, 'exito');
            // Recargar la página después de 1.5 segundos para mostrar la info actualizada
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensajeConfig(data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarMensajeConfig('Error al guardar la fecha', 'error');
    }
}

async function limpiarFechaRevelacion() {
    if (!confirm('¿Estás seguro de eliminar la fecha de revelación? Se ocultará la cuenta regresiva.')) {
        return;
    }
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                accion: 'limpiar_fecha_revelacion'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensajeConfig(data.mensaje, 'exito');
            // Recargar la página después de 1.5 segundos
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarMensajeConfig(data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarMensajeConfig('Error al limpiar la fecha', 'error');
    }
}

function mostrarMensajeConfig(mensaje, tipo) {
    const mensajeDiv = document.getElementById('mensajeConfig');
    if (mensajeDiv) {
        mensajeDiv.textContent = mensaje;
        mensajeDiv.style.display = 'block';
        mensajeDiv.className = tipo === 'exito' ? 'mensaje-exito' : 'error-message';
        
        setTimeout(() => {
            mensajeDiv.style.display = 'none';
        }, 3000);
    }
}

// =====================
// CATEGORÍAS
// =====================
async function cargarCategorias() {
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'listar_categorias'})
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarCategorias(data.categorias);
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

function mostrarCategorias(categorias) {
    const container = document.getElementById('listaCategorias');
    
    if (categorias.length === 0) {
        container.innerHTML = '<p class="text-center">No hay categorías creadas</p>';
        return;
    }
    
    let html = '<table class="tabla-admin"><thead><tr><th>Orden</th><th>Nombre</th><th>Nominados</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
    
    categorias.forEach(cat => {
        html += `
            <tr>
                <td>${cat.orden}</td>
                <td><strong>${cat.nombre}</strong><br><small>${cat.descripcion || ''}</small></td>
                <td>${cat.total_nominados}</td>
                <td><span class="badge ${cat.visible ? 'badge-success' : 'badge-danger'}">${cat.visible ? 'Visible' : 'Oculta'}</span></td>
                <td>
                    <button class="btn-icon" onclick="editarCategoria(${cat.id})" title="Editar">✏️</button>
                    <button class="btn-icon" onclick="eliminarCategoria(${cat.id})" title="Eliminar">🗑️</button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

function mostrarModalCategoria(id = null) {
    const modal = document.getElementById('modalCategoria');
    const titulo = document.getElementById('tituloModalCategoria');
    const form = document.getElementById('formCategoria');
    
    form.reset();
    document.getElementById('categoriaId').value = '';
    
    if (id) {
        titulo.textContent = 'Editar Categoría';
        cargarDatosCategoria(id);
    } else {
        titulo.textContent = 'Nueva Categoría';
    }
    
    modal.style.display = 'block';
}

function cerrarModalCategoria() {
    document.getElementById('modalCategoria').style.display = 'none';
}

async function cargarDatosCategoria(id) {
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'listar_categorias'})
        });
        
        const data = await response.json();
        
        if (data.success) {
            const categoria = data.categorias.find(c => c.id == id);
            if (categoria) {
                document.getElementById('categoriaId').value = categoria.id;
                document.getElementById('categoriaNombre').value = categoria.nombre;
                document.getElementById('categoriaDescripcion').value = categoria.descripcion || '';
                document.getElementById('categoriaOrden').value = categoria.orden;
                document.getElementById('categoriaVisible').checked = categoria.visible == 1;
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function guardarCategoria(event) {
    event.preventDefault();
    
    const id = document.getElementById('categoriaId').value;
    const nombre = document.getElementById('categoriaNombre').value.trim();
    const descripcion = document.getElementById('categoriaDescripcion').value.trim();
    const orden = document.getElementById('categoriaOrden').value;
    const visible = document.getElementById('categoriaVisible').checked ? 'true' : 'false';
    
    const accion = id ? 'actualizar_categoria' : 'crear_categoria';
    
    const formData = new URLSearchParams({
        accion: accion,
        nombre: nombre,
        descripcion: descripcion,
        orden: orden,
        visible: visible
    });
    
    if (id) formData.append('id', id);
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            cerrarModalCategoria();
            cargarCategorias();
            alert(data.mensaje);
        } else {
            document.getElementById('errorCategoria').textContent = data.mensaje;
            document.getElementById('errorCategoria').style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar la categoría');
    }
}

function editarCategoria(id) {
    mostrarModalCategoria(id);
}

async function eliminarCategoria(id) {
    if (!confirm('¿Estás seguro de eliminar esta categoría?')) return;
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'eliminar_categoria', id: id})
        });
        
        const data = await response.json();
        
        if (data.success) {
            cargarCategorias();
            alert(data.mensaje);
        } else {
            alert(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar la categoría');
    }
}

// =====================
// NOMINADOS
// =====================
async function cargarNominados() {
    const categoriaId = document.getElementById('filtroCategoria')?.value || '';
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'listar_nominados', categoria_id: categoriaId})
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNominados(data.nominados);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarNominados(nominados) {
    const container = document.getElementById('listaNominados');
    
    if (nominados.length === 0) {
        container.innerHTML = '<p class="text-center">No hay nominados</p>';
        return;
    }
    
    let html = '';
    
    nominados.forEach(nom => {
        const tipoMedia = nom.tipo_media || 'imagen';
        const mediaHtml = tipoMedia === 'video' 
            ? `<video src="${nom.imagen_url}" controls style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;"></video>`
            : `<img src="${nom.imagen_url}" alt="${nom.nombre}">`;
        
        const badgeTipo = tipoMedia === 'video' 
            ? '<span style="background: #ef4444; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; display: inline-block; margin-bottom: 0.5rem;">📹 VIDEO</span>'
            : '<span style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; display: inline-block; margin-bottom: 0.5rem;">🖼️ IMAGEN</span>';
        
        html += `
            <div class="nominado-admin-card">
                ${mediaHtml}
                ${badgeTipo}
                <h4>${nom.nombre}</h4>
                <p class="categoria-tag">${nom.categoria_nombre}</p>
                <p>${nom.descripcion || ''}</p>
                <div class="card-actions">
                    <button class="btn-icon" onclick="editarNominado(${nom.id})" title="Editar">✏️</button>
                    <button class="btn-icon" onclick="eliminarNominado(${nom.id})" title="Eliminar">🗑️</button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

async function cargarCategoriasSelect() {
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'listar_categorias'})
        });
        
        const data = await response.json();
        
        if (data.success) {
            const selectFiltro = document.getElementById('filtroCategoria');
            const selectNominado = document.getElementById('nominadoCategoria');
            
            let options = '<option value="">Todas las categorías</option>';
            let optionsNominado = '<option value="">Selecciona una categoría</option>';
            
            data.categorias.forEach(cat => {
                options += `<option value="${cat.id}">${cat.nombre}</option>`;
                optionsNominado += `<option value="${cat.id}">${cat.nombre}</option>`;
            });
            
            if (selectFiltro) selectFiltro.innerHTML = options;
            if (selectNominado) selectNominado.innerHTML = optionsNominado;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarModalNominado(id = null) {
    const modal = document.getElementById('modalNominado');
    const titulo = document.getElementById('tituloModalNominado');
    const form = document.getElementById('formNominado');
    
    form.reset();
    document.getElementById('nominadoId').value = '';
    
    const tipoMediaElement = document.getElementById('tipoMedia');
    if (tipoMediaElement) {
        tipoMediaElement.value = 'imagen';
        actualizarLabelURL();
    }
    
    const preview = document.getElementById('previewImage');
    if (preview) {
        preview.style.display = 'none';
        preview.src = '';
    }
    
    const dropAreaText = document.querySelector('#dropArea p');
    if (dropAreaText) {
        dropAreaText.style.display = 'block';
    }
    
    if (id) {
        titulo.textContent = 'Editar Nominado';
        cargarDatosNominado(id);
    } else {
        titulo.textContent = 'Nuevo Nominado';
    }
    
    setTimeout(() => {
        initUploadArea();
    }, 100);
    
    modal.style.display = 'block';
}

function cerrarModalNominado() {
    document.getElementById('modalNominado').style.display = 'none';
}

function actualizarLabelURL() {
    const tipoElement = document.getElementById('tipoMedia');
    if (!tipoElement) return;
    
    const tipo = tipoElement.value;
    const uploadArea = document.getElementById('uploadArea');
    const urlInputArea = document.getElementById('urlInputArea');
    
    if (!uploadArea || !urlInputArea) return;
    
    if (tipo === 'video') {
        uploadArea.style.display = 'none';
        urlInputArea.style.display = 'block';
    } else {
        uploadArea.style.display = 'block';
        urlInputArea.style.display = 'none';
    }
}

function initUploadArea() {
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('fileInput');
    const preview = document.getElementById('previewImage');
    const urlInput = document.getElementById('nominadoImagen');
    
    if (!dropArea || !fileInput) return;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => {
            dropArea.classList.add('highlight');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => {
            dropArea.classList.remove('highlight');
        }, false);
    });
    
    dropArea.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    }, false);
    
    dropArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });
    
    async function handleFile(file) {
        if (!file.type.startsWith('image/')) {
            alert('Por favor selecciona una imagen válida');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert('La imagen es demasiado grande. Máximo 5MB');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            dropArea.querySelector('p').style.display = 'none';
        };
        reader.readAsDataURL(file);
        
        await uploadImage(file);
    }
    
    async function uploadImage(file) {
        const formData = new FormData();
        formData.append('imagen', file);
        
        dropArea.classList.add('uploading');
        
        try {
            const response = await fetch('upload_imagen.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                urlInput.value = data.url;
                dropArea.classList.remove('uploading');
                dropArea.classList.add('uploaded');
                
                setTimeout(() => {
                    dropArea.classList.remove('uploaded');
                }, 2000);
            } else {
                throw new Error(data.mensaje);
            }
        } catch (error) {
            console.error('Error al subir imagen:', error);
            alert('Error al subir la imagen: ' + error.message);
            dropArea.classList.remove('uploading');
            preview.style.display = 'none';
            dropArea.querySelector('p').style.display = 'block';
        }
    }
}

async function cargarDatosNominado(id) {
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'listar_nominados'})
        });
        
        const data = await response.json();
        
        if (data.success) {
            const nominado = data.nominados.find(n => n.id == id);
            if (nominado) {
                document.getElementById('nominadoId').value = nominado.id;
                document.getElementById('nominadoCategoria').value = nominado.categoria_id;
                document.getElementById('nominadoNombre').value = nominado.nombre;
                document.getElementById('nominadoDescripcion').value = nominado.descripcion || '';
                
                const tipoMediaElement = document.getElementById('tipoMedia');
                if (tipoMediaElement) {
                    tipoMediaElement.value = nominado.tipo_media || 'imagen';
                    actualizarLabelURL();
                    
                    if (nominado.tipo_media === 'imagen') {
                        document.getElementById('nominadoImagen').value = nominado.imagen_url;
                        const preview = document.getElementById('previewImage');
                        if (preview) {
                            preview.src = nominado.imagen_url;
                            preview.style.display = 'block';
                            const dropAreaText = document.querySelector('#dropArea p');
                            if (dropAreaText) {
                                dropAreaText.style.display = 'none';
                            }
                        }
                    } else {
                        document.getElementById('videoUrl').value = nominado.imagen_url;
                    }
                }
                
                setTimeout(() => {
                    initUploadArea();
                }, 100);
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function guardarNominado(event) {
    event.preventDefault();
    
    const id = document.getElementById('nominadoId').value;
    const categoriaId = document.getElementById('nominadoCategoria').value;
    const nombre = document.getElementById('nominadoNombre').value.trim();
    const descripcion = document.getElementById('nominadoDescripcion').value.trim();
    
    const tipoMediaElement = document.getElementById('tipoMedia');
    const tipoMedia = tipoMediaElement ? tipoMediaElement.value : 'imagen';
    
    let imagenUrl = '';
    if (tipoMedia === 'video') {
        imagenUrl = document.getElementById('videoUrl').value.trim();
    } else {
        imagenUrl = document.getElementById('nominadoImagen').value.trim();
    }
    
    if (!imagenUrl) {
        const errorDiv = document.getElementById('errorNominado');
        errorDiv.textContent = tipoMedia === 'video' 
            ? 'Por favor ingresa la URL del video' 
            : 'Por favor sube una imagen';
        errorDiv.style.display = 'block';
        return;
    }
    
    const accion = id ? 'actualizar_nominado' : 'crear_nominado';
    
    const formData = new URLSearchParams({
        accion: accion,
        categoria_id: categoriaId,
        nombre: nombre,
        descripcion: descripcion,
        imagen_url: imagenUrl,
        tipo_media: tipoMedia
    });
    
    if (id) formData.append('id', id);
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            cerrarModalNominado();
            cargarNominados();
            alert(data.mensaje);
        } else {
            document.getElementById('errorNominado').textContent = data.mensaje;
            document.getElementById('errorNominado').style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar el nominado');
    }
}

function editarNominado(id) {
    mostrarModalNominado(id);
}

async function eliminarNominado(id) {
    if (!confirm('¿Estás seguro de eliminar este nominado? También se eliminarán sus votos.')) return;
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({accion: 'eliminar_nominado', id: id})
        });
        
        const data = await response.json();
        
        if (data.success) {
            cargarNominados();
            alert(data.mensaje);
        } else {
            alert(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar el nominado');
    }
}

// =====================
// RESULTADOS
// =====================
// ================================================
// RESULTADOS
// ================================================

async function actualizarResultados() {
    console.log('🔍 Actualizando resultados...');
    
    const container = document.getElementById('resultadosContainer');
    
    if (!container) {
        console.error('❌ Container de resultados no encontrado');
        alert('Error: No se puede cargar la sección de resultados.');
        return;
    }
    
    console.log('✅ Container encontrado');
    container.innerHTML = '<div class="loading">⏳ Cargando resultados...</div>';
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'accion=obtener_resultados'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📊 Datos recibidos:', data);
        
        if (data.success) {
            if (!data.categorias || data.categorias.length === 0) {
                container.innerHTML = `
                    <div class="no-resultados">
                        <div class="no-resultados-icon">📊</div>
                        <h3>No hay resultados aún</h3>
                        <p>Aún no se han registrado votos en las categorías.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            
            data.categorias.forEach(categoria => {
                const categoriaDiv = crearCardResultado(categoria);
                container.appendChild(categoriaDiv);
            });
            
            console.log('✅ Resultados cargados correctamente');
            mostrarNotificacionAdmin('Resultados actualizados', 'success');
        } else {
            throw new Error(data.mensaje || 'Error desconocido');
        }
    } catch (error) {
        console.error('❌ Error al actualizar resultados:', error);
        container.innerHTML = `
            <div class="error" style="text-align: center; padding: 2rem; color: #ef4444;">
                <h3>❌ Error al cargar resultados</h3>
                <p>${error.message}</p>
                <button class="btn btn-secondary" onclick="actualizarResultados()" style="margin-top: 1rem;">
                    🔄 Reintentar
                </button>
            </div>
        `;
    }
}

function crearCardResultado(categoria) {
    const div = document.createElement('div');
    div.className = 'resultado-categoria';
    
    let resultadosHTML = '';
    
    if (categoria.resultados && categoria.resultados.length > 0) {
        categoria.resultados.forEach(resultado => {
            const porcentaje = categoria.total_votos > 0 
                ? Math.round((resultado.votos / categoria.total_votos) * 100 * 100) / 100
                : 0;
            
            resultadosHTML += `
                <div class="resultado-item">
                    <div class="resultado-info">
                        <img src="${resultado.imagen_url || ''}" 
                             alt="${resultado.nombre || ''}" 
                             class="resultado-img"
                             onerror="this.style.display='none'">
                        <div>
                            <h4>${resultado.nombre || 'Sin nombre'}</h4>
                            <p>${resultado.votos || 0} votos (${porcentaje}%)</p>
                        </div>
                    </div>
                    <div class="resultado-barra">
                        <div class="barra-progreso" style="width: ${porcentaje}%"></div>
                    </div>
                </div>
            `;
        });
    } else {
        resultadosHTML = '<p class="sin-votos">No hay votos en esta categoría aún</p>';
    }
    
    div.innerHTML = `
        <h3>${categoria.nombre || 'Sin nombre'}</h3>
        <p class="total-votos-cat">Total de votos: ${categoria.total_votos || 0}</p>
        <div class="resultados-tabla">
            ${resultadosHTML}
        </div>
    `;
    
    return div;
}

function mostrarNotificacionAdmin(mensaje, tipo = 'success') {
    let notif = document.getElementById('notificacionAdmin');
    
    if (!notif) {
        notif = document.createElement('div');
        notif.id = 'notificacionAdmin';
        notif.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
            max-width: 300px;
        `;
        document.body.appendChild(notif);
    }
    
    const colores = {
        success: { bg: 'rgba(34, 197, 94, 0.95)', color: '#fff' },
        error: { bg: 'rgba(239, 68, 68, 0.95)', color: '#fff' },
        info: { bg: 'rgba(59, 130, 246, 0.95)', color: '#fff' }
    };
    
    const color = colores[tipo] || colores.info;
    notif.style.background = color.bg;
    notif.style.color = color.color;
    notif.textContent = mensaje;
    notif.style.display = 'block';
    
    setTimeout(() => {
        notif.style.opacity = '0';
        setTimeout(() => {
            notif.style.display = 'none';
            notif.style.opacity = '1';
        }, 300);
    }, 3000);
}

console.log('✅ Módulo de resultados cargado');

// Agregar animación CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .sin-votos {
        text-align: center;
        padding: 2rem;
        color: var(--admin-text-secondary);
        font-style: italic;
    }
    
    .no-resultados {
        text-align: center;
        padding: 4rem 2rem;
    }
    
    .no-resultados-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .no-resultados h3 {
        color: var(--admin-text);
        margin-bottom: 0.5rem;
    }
    
    .no-resultados p {
        color: var(--admin-text-secondary);
    }
`;
document.head.appendChild(style);

// =====================
// CERRAR SESIÓN
// =====================
async function cerrarSesion() {
    if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        return;
    }
    
    try {
        const response = await fetch('auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                accion: 'logout'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert('Error al cerrar sesión');
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        window.location.href = 'index.php';
    }
}

// Cerrar modales al hacer clic fuera
window.onclick = function(event) {
    if (event.target.id === 'modalCategoria') {
        cerrarModalCategoria();
    }
    if (event.target.id === 'modalNominado') {
        cerrarModalNominado();
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Panel de administración inicializado');
});
// ================================================
// GESTIÓN DE RESPUESTAS DE TEXTO - ADMIN
// ================================================

// Cambiar entre tabs
function cambiarTabRespuestas(tab) {
    // Cambiar botones activos
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Cambiar contenido
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
        content.style.display = 'none';
    });
    
    if (tab === 'categoria') {
        document.getElementById('tabCategoria').style.display = 'block';
        document.getElementById('tabCategoria').classList.add('active');
        cargarCategoriasTextoLibre();
    } else {
        document.getElementById('tabUsuario').style.display = 'block';
        document.getElementById('tabUsuario').classList.add('active');
        cargarUsuariosConRespuestas();
    }
}

// Cargar categorías de texto libre para el filtro
async function cargarCategoriasTextoLibre() {
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'accion=listar_categorias'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('filtroCategoriaRespuestas');
            const categoriasTexto = data.categorias.filter(c => c.tipo_votacion === 'texto_libre');
            
            select.innerHTML = '<option value="">Todas las categorías de texto libre</option>';
            
            categoriasTexto.forEach(cat => {
                select.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
            });
            
            cargarRespuestasPorCategoria();
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

// Cargar respuestas por categoría
async function cargarRespuestasPorCategoria() {
    const categoriaId = document.getElementById('filtroCategoriaRespuestas').value;
    const container = document.getElementById('listaRespuestasCategoria');
    
    container.innerHTML = '<div class="loading">Cargando respuestas...</div>';
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=listar_respuestas_texto&categoria_id=${categoriaId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.respuestas.length === 0) {
                container.innerHTML = `
                    <div class="no-respuestas">
                        <div class="no-respuestas-icon">📝</div>
                        <h3>No hay respuestas aún</h3>
                        <p>Los usuarios aún no han enviado respuestas en estas categorías.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            
            data.respuestas.forEach(respuesta => {
                const card = crearCardRespuesta(respuesta);
                container.appendChild(card);
            });
        }
    } catch (error) {
        console.error('Error al cargar respuestas:', error);
        container.innerHTML = '<div class="error">Error al cargar respuestas</div>';
    }
}

// Cargar usuarios con respuestas
async function cargarUsuariosConRespuestas() {
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'accion=listar_usuarios_con_respuestas'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('filtroUsuarioRespuestas');
            
            select.innerHTML = '<option value="">Selecciona un usuario</option>';
            
            data.usuarios.forEach(usuario => {
                select.innerHTML += `
                    <option value="${usuario.id}">
                        ${usuario.nombre} (${usuario.total_respuestas} respuestas)
                    </option>
                `;
            });
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    }
}

// Cargar respuestas por usuario
async function cargarRespuestasPorUsuario() {
    const usuarioId = document.getElementById('filtroUsuarioRespuestas').value;
    const container = document.getElementById('listaRespuestasUsuario');
    
    if (!usuarioId) {
        container.innerHTML = `
            <div class="no-respuestas">
                <div class="no-respuestas-icon">👤</div>
                <p>Selecciona un usuario para ver sus respuestas</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '<div class="loading">Cargando respuestas...</div>';
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=obtener_respuestas_usuario&usuario_id=${usuarioId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.respuestas.length === 0) {
                container.innerHTML = `
                    <div class="no-respuestas">
                        <div class="no-respuestas-icon">📝</div>
                        <p>Este usuario no ha enviado respuestas</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            
            data.respuestas.forEach(respuesta => {
                const card = crearCardRespuesta(respuesta);
                container.appendChild(card);
            });
        }
    } catch (error) {
        console.error('Error al cargar respuestas:', error);
        container.innerHTML = '<div class="error">Error al cargar respuestas</div>';
    }
}

// Crear card de respuesta
function crearCardRespuesta(respuesta) {
    const card = document.createElement('div');
    card.className = 'respuesta-card-admin';
    card.id = `respuesta-${respuesta.id}`;
    
    const fecha = new Date(respuesta.fecha_respuesta).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    card.innerHTML = `
        <div class="respuesta-header-admin">
            <div class="respuesta-meta">
                <h4>${respuesta.categoria_nombre}</h4>
                <div class="meta-info">
                    <div class="meta-item">
                        <span>👤</span>
                        <strong>${respuesta.usuario_nombre}</strong>
                    </div>
                    <div class="meta-item">
                        <span>📧</span>
                        ${respuesta.usuario_email}
                    </div>
                    <div class="meta-item">
                        <span>📅</span>
                        ${fecha}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="respuesta-contenido-admin">
            <p class="respuesta-texto-admin">${escapeHtml(respuesta.respuesta)}</p>
        </div>
        
        <div class="respuesta-actions">
            <button class="btn-eliminar-respuesta-admin" onclick="eliminarRespuestaAdmin(${respuesta.id})">
                🗑️ Eliminar Respuesta
            </button>
        </div>
    `;
    
    return card;
}

// Eliminar respuesta (admin)
async function eliminarRespuestaAdmin(respuestaId) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta respuesta? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=eliminar_respuesta_admin&respuesta_id=${respuestaId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Eliminar visualmente con animación
            const card = document.getElementById(`respuesta-${respuestaId}`);
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                card.remove();
                
                // Verificar si quedan respuestas
                const container = card.parentElement;
                if (container.children.length === 0) {
                    container.innerHTML = `
                        <div class="no-respuestas">
                            <div class="no-respuestas-icon">📝</div>
                            <p>No hay más respuestas</p>
                        </div>
                    `;
                }
            }, 300);
            
            mostrarMensajeAdmin(data.mensaje, 'success');
        } else {
            mostrarMensajeAdmin(data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error al eliminar respuesta:', error);
        mostrarMensajeAdmin('Error al eliminar la respuesta', 'error');
    }
}

// Mostrar mensaje admin
function mostrarMensajeAdmin(mensaje, tipo) {
    // Crear o usar elemento de mensaje existente
    let mensajeDiv = document.getElementById('mensajeAdminGlobal');
    
    if (!mensajeDiv) {
        mensajeDiv = document.createElement('div');
        mensajeDiv.id = 'mensajeAdminGlobal';
        mensajeDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
        `;
        document.body.appendChild(mensajeDiv);
    }
    
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = tipo === 'success' ? 'mensaje-exito' : 'mensaje-error';
    mensajeDiv.style.display = 'block';
    
    setTimeout(() => {
        mensajeDiv.style.display = 'none';
    }, 3000);
}

// Función auxiliar para escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

console.log('✅ admin-respuestas.js cargado');