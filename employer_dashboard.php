<?php
include "php/session_check.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/jobs.css">
    <title>Panel del Empleador</title>
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            color: white;
        }
        
        .dashboard-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        
        .tab-btn {
            padding: 15px 25px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .job-card-dashboard {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .job-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .job-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-published { background: #e3f2fd; color: #1976d2; }
        .status-assigned { background: #fff3e0; color: #f57c00; }
        .status-in_progress { background: #e3f2fd; color: #1976d2; }
        .status-completed { background: #e8f5e9; color: #388e3c; }
        
        .btn-chat {
            background: #2196f3;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-chat:hover {
            background: #0b7dda;
        }
        
        .responses-badge {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .responses-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .response-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .response-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-accept, .btn-reject {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-accept {
            background: #4caf50;
            color: white;
        }
        
        .btn-accept:hover {
            background: #45a049;
        }
        
        .btn-reject {
            background: #f44336;
            color: white;
        }
        
        .btn-reject:hover {
            background: #da190b;
        }
        
        .contact-info {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .contact-info h4 {
            margin: 0 0 10px 0;
            color: #2e7d32;
        }
        
        .contact-info p {
            margin: 5px 0;
            color: #333;
        }
        
        .contact-info i {
            margin-right: 8px;
            color: #4caf50;
        }
    </style>
</head>
<body>
    <header>
        <i class='bx bxs-home-alt-2 bx-lg' onclick="window.location.href='index.php'"></i>
        <i class='bx bx-log-out bx-lg' onclick="window.location.href='php/logout.php'" style="float: right; margin-right: 20px; cursor: pointer;"></i>
    </header>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1>Panel del Empleador</h1>
                <p>Bienvenido, <?php echo htmlspecialchars($_SESSION["user_name"] ?? "Usuario"); ?>!</p>
            </div>
            <a href="form-jobs.html" style="background: white; color: #667eea; padding: 12px 24px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                <i class='bx bx-plus'></i> Publicar Trabajo
            </a>
        </div>
        
        <div class="dashboard-tabs">
            <button class="tab-btn active" onclick="showTab('published')">Mis Trabajos</button>
            <button class="tab-btn" onclick="showTab('responses')">Solicitudes</button>
        </div>
        
        <div id="published-tab" class="tab-content active">
            <div id="jobs-list"></div>
        </div>
        
        <div id="responses-tab" class="tab-content">
            <div id="responses-list"></div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            
            if (tabName === 'published') {
                loadMyJobs();
            } else {
                loadResponses();
            }
        }
        
        async function loadMyJobs() {
            try {
                const response = await fetch('php/get_employer_jobs.php', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('jobs-list');
                    if (data.data.length === 0) {
                        container.innerHTML = '<p style="text-align: center; padding: 40px;">No has publicado ningún trabajo aún.</p>';
                        return;
                    }
                    
                    container.innerHTML = data.data.map(job => {
                        const statusClass = `status-${job.status}`;
                        const statusText = {
                            'published': 'Publicado',
                            'assigned': 'Asignado',
                            'in_progress': 'En Progreso',
                            'completed': 'Completado'
                        }[job.status] || job.status;
                        
                        const hasPendingCompletion = job.has_pending_completion == 1 || job.assignment_status === 'pending_completion';
                        const isDisputed = job.assignment_status === 'disputed';
                        
                        return `
                            <div class="job-card-dashboard">
                                <div class="job-card-header">
                                    <div>
                                        <h2>${job.title}</h2>
                                        <p>${job.category_name || ''} - ${job.subcategory_name || ''}</p>
                                        <p><i class='bx bx-time'></i> ${job.duration_hours} horas | <i class='bx bx-map'></i> ${job.city || ''}, ${job.province || ''}</p>
                                    </div>
                                    <div>
                                        <span class="job-status ${statusClass}">${statusText}</span>
                                        ${parseInt(job.pending_responses) > 0 ? `<span class="responses-badge">${job.pending_responses} solicitudes</span>` : ''}
                                        ${hasPendingCompletion ? `<span class="responses-badge" style="background: #ff9800;">⚠️ Pendiente Confirmación</span>` : ''}
                                        ${isDisputed ? `<span class="responses-badge" style="background: #f44336;">⚠️ En Disputa</span>` : ''}
                                    </div>
                                </div>
                                <p>${job.description}</p>
                                ${hasPendingCompletion ? `
                                    <div style="background: #fff3e0; border: 1px solid #ff9800; border-radius: 8px; padding: 15px; margin-top: 15px;">
                                        <h4 style="color: #f57c00; margin: 0 0 10px 0;">⏳ Esperando Tu Confirmación</h4>
                                        <p style="margin: 5px 0;">El trabajador ha marcado este trabajo como completado. Por favor revisa y confirma.</p>
                                        <button class="btn-accept" onclick="viewCompletionDetails(${job.id})" style="margin-top: 10px;">Ver Detalles y Confirmar</button>
                                    </div>
                                ` : ''}
                                ${isDisputed ? `
                                    <div style="background: #ffebee; border: 1px solid #f44336; border-radius: 8px; padding: 15px; margin-top: 15px;">
                                        <h4 style="color: #c62828; margin: 0 0 10px 0;">⚠️ Proceso de Verificación</h4>
                                        <p style="margin: 5px 0;">Hay una disputa sobre la finalización de este trabajo.</p>
                                        <button class="btn-chat" onclick="viewDispute(${job.id})" style="margin-top: 10px; background: #f44336;">Ver Disputa</button>
                                    </div>
                                ` : ''}
                                ${parseInt(job.total_responses) > 0 ? `
                                    <div class="responses-section">
                                        <h3>Solicitudes (${job.total_responses})</h3>
                                        <button class="btn-chat" onclick="viewResponses(${job.id})">Ver Solicitudes</button>
                                    </div>
                                ` : '<p style="color: #999; margin-top: 10px;">Aún no hay solicitudes</p>'}
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function viewResponses(taskId) {
            try {
                const response = await fetch(`php/get_job_responses.php?task_id=${taskId}`, {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success) {
                    const modal = document.createElement('div');
                    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; display: flex; align-items: center; justify-content: center;';
                    modal.innerHTML = `
                        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h2>Solicitudes</h2>
                                <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" style="background: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cerrar</button>
                            </div>
                            <div id="responses-modal-content">
                                ${data.data.map(resp => {
                                    const statusText = {
                                        'requested': 'Pendiente',
                                        'selected': 'Seleccionado',
                                        'rejected': 'Rechazado'
                                    }[resp.status] || resp.status;
                                    
                                    return `
                                        <div class="response-card">
                                            <div class="response-header">
                                                <div>
                                                    <strong>${resp.name} ${resp.last_name}</strong>
                                                    <p style="margin: 5px 0; color: #666;">${resp.email}</p>
                                                    ${resp.phone_number ? `<p style="margin: 0; color: #666;"><i class='bx bx-phone'></i> ${resp.phone_number}</p>` : ''}
                                                </div>
                                                <span class="job-status status-${resp.status}">${statusText}</span>
                                            </div>
                                            ${resp.message ? `<p style="margin: 10px 0; padding: 10px; background: white; border-radius: 5px;">${resp.message}</p>` : ''}
                                            <p style="font-size: 12px; color: #999;">Solicitado: ${new Date(resp.created_at).toLocaleString()}</p>
                                            ${resp.status === 'requested' ? `
                                                <div class="response-actions">
                                                    <button class="btn-accept" onclick="acceptResponse(${resp.id}, ${resp.task_id}, ${resp.worker_id})">Aceptar</button>
                                                    <button class="btn-reject" onclick="rejectResponse(${resp.id}, ${resp.task_id})">Rechazar</button>
                                                </div>
                                            ` : ''}
                                            ${resp.assignment_status === 'assigned' || resp.assignment_status === 'in_progress' ? `
                                                <div class="contact-info">
                                                    <h4>✅ Trabajador Asignado</h4>
                                                    <p><i class='bx bx-user'></i><strong>Nombre:</strong> ${resp.name} ${resp.last_name}</p>
                                                    <p><i class='bx bx-envelope'></i><strong>Email:</strong> <a href="mailto:${resp.email}" style="color: #2e7d32; text-decoration: none;">${resp.email}</a></p>
                                                    ${resp.phone_number ? `<p><i class='bx bx-phone'></i><strong>Teléfono:</strong> <a href="tel:${resp.phone_number}" style="color: #2e7d32; text-decoration: none;">${resp.phone_number}</a></p>` : ''}
                                                </div>
                                            ` : ''}
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar las solicitudes');
            }
        }
        
        async function acceptResponse(responseId, taskId, workerId) {
            if (!confirm('¿Estás seguro de aceptar esta solicitud? Se rechazarán automáticamente las demás solicitudes.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('response_id', responseId);
                formData.append('task_id', taskId);
                formData.append('worker_id', workerId);
                
                const response = await fetch('php/accept_job_response.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al aceptar la solicitud');
            }
        }
        
        async function rejectResponse(responseId, taskId) {
            if (!confirm('¿Estás seguro de rechazar esta solicitud?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('response_id', responseId);
                formData.append('task_id', taskId);
                
                const response = await fetch('php/reject_job_response.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al rechazar la solicitud');
            }
        }
        
        async function viewCompletionDetails(taskId) {
            try {
                const response = await fetch(`php/get_job_responses.php?task_id=${taskId}`, {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    const assigned = data.data.find(resp => resp.assignment_status === 'pending_completion' || resp.assignment_status === 'disputed');
                    
                    if (assigned) {
                        const modal = document.createElement('div');
                        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; display: flex; align-items: center; justify-content: center;';
                        modal.innerHTML = `
                            <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h2>Confirmar Finalización</h2>
                                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" style="background: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cerrar</button>
                                </div>
                                <div style="margin-bottom: 20px;">
                                    <p><strong>Trabajador:</strong> ${assigned.name} ${assigned.last_name}</p>
                                    <p><strong>Email:</strong> ${assigned.email}</p>
                                    ${assigned.phone_number ? `<p><strong>Teléfono:</strong> ${assigned.phone_number}</p>` : ''}
                                    <p style="margin-top: 15px;"><strong>Completado el:</strong> ${new Date(assigned.completed_at || new Date()).toLocaleString()}</p>
                                </div>
                                <div id="alert-completion" style="display: none; padding: 15px; margin: 15px 0; border-radius: 5px;"></div>
                                <div style="display: flex; gap: 10px; justify-content: center;">
                                    <button class="btn-accept" onclick="confirmCompletion(${taskId})">✅ Confirmar Completado</button>
                                    <button class="btn-reject" onclick="showRejectForm(${taskId})">❌ Rechazar</button>
                                </div>
                                <div id="reject-form-${taskId}" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                                    <h4>Razón del rechazo:</h4>
                                    <textarea id="reject-reason-${taskId}" placeholder="Explica por qué no puedes confirmar la finalización..." style="width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin: 10px 0;"></textarea>
                                    <button class="btn-reject" onclick="rejectCompletion(${taskId})">Enviar Rechazo</button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar los detalles');
            }
        }
        
        async function confirmCompletion(taskId) {
            try {
                const formData = new FormData();
                formData.append('task_id', taskId);
                
                const response = await fetch('php/confirm_completion.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al confirmar el trabajo');
            }
        }
        
        function showRejectForm(taskId) {
            document.getElementById('reject-form-' + taskId).style.display = 'block';
        }
        
        async function rejectCompletion(taskId) {
            const reason = document.getElementById('reject-reason-' + taskId).value.trim();
            
            if (!reason) {
                alert('Por favor proporciona una razón para rechazar la finalización.');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('task_id', taskId);
                formData.append('reason', reason);
                
                const response = await fetch('php/reject_completion.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al rechazar la finalización');
            }
        }
        
        async function viewDispute(taskId) {
            try {
                const response = await fetch(`php/get_dispute.php?task_id=${taskId}`, {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success) {
                    const dispute = data.data;
                    const modal = document.createElement('div');
                    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; display: flex; align-items: center; justify-content: center;';
                    modal.innerHTML = `
                        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h2>Proceso de Verificación</h2>
                                <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" style="background: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cerrar</button>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <h3>Mensaje del Trabajador:</h3>
                                <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;">
                                    <p>${dispute.worker_message || 'Sin mensaje del trabajador'}</p>
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <h3>Tu Mensaje:</h3>
                                <div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 10px 0;">
                                    <p>${dispute.employer_message || 'Sin mensaje'}</p>
                                </div>
                            </div>
                            <div id="alert-dispute" style="display: none; padding: 15px; margin: 15px 0; border-radius: 5px;"></div>
                            <div style="margin-top: 20px;">
                                <h4>Agregar Mensaje:</h4>
                                <textarea id="dispute-message" placeholder="Escribe tu argumento..." style="width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin: 10px 0;"></textarea>
                                <button class="btn-chat" onclick="addDisputeMessage(${taskId})">Enviar Mensaje</button>
                            </div>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #ddd;">
                                <p><strong>¿Resolviste la disputa?</strong></p>
                                <div style="display: flex; gap: 10px; margin-top: 10px;">
                                    <button class="btn-accept" onclick="confirmCompletion(${taskId})">✅ Confirmar Finalización</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar la disputa');
            }
        }
        
        async function addDisputeMessage(taskId) {
            const message = document.getElementById('dispute-message').value.trim();
            
            if (!message) {
                alert('Por favor escribe un mensaje.');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('task_id', taskId);
                formData.append('message', message);
                
                const response = await fetch('php/add_dispute_message.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('dispute-message').value = '';
                    alert(data.message);
                    viewDispute(taskId);
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al enviar el mensaje');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadMyJobs();
        });
    </script>
</body>
</html>

