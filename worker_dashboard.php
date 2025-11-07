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
    <title>Panel del Trabajador</title>
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            color: #11998e;
            border-bottom-color: #11998e;
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
        
        .response-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-requested { background: #e3f2fd; color: #1976d2; }
        .status-selected { background: #fff3e0; color: #f57c00; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .assignment-assigned { background: #e8f5e9; color: #388e3c; }
        .assignment-done { background: #f1f8e9; color: #689f38; }
        
        .job-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-complete {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            background: #4caf50;
            color: white;
        }
        
        .btn-complete:hover {
            background: #45a049;
        }
        
        .contact-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .contact-info h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        
        .contact-info p {
            margin: 5px 0;
            color: #333;
        }
        
        .contact-info i {
            margin-right: 8px;
            color: #2196f3;
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
                <h1>Panel del Trabajador</h1>
                <p>Bienvenido, <?php echo htmlspecialchars($_SESSION["user_name"] ?? "Usuario"); ?>!</p>
            </div>
            <a href="jobs.php" style="background: white; color: #11998e; padding: 12px 24px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                <i class='bx bx-search'></i> Buscar Trabajos
            </a>
        </div>
        
        <div class="dashboard-tabs">
            <button class="tab-btn active" onclick="showTab('requests')">Mis Solicitudes</button>
            <button class="tab-btn" onclick="showTab('assigned')">Trabajos Asignados</button>
            <button class="tab-btn" onclick="showTab('completed')">Completados</button>
            <button class="tab-btn" onclick="showTab('subscription')">Suscripción</button>
        </div>
        
        <div id="requests-tab" class="tab-content active">
            <div id="requests-list"></div>
        </div>
        
        <div id="assigned-tab" class="tab-content">
            <div id="assigned-list"></div>
        </div>
        
        <div id="completed-tab" class="tab-content">
            <div id="completed-list"></div>
        </div>
        
        <div id="subscription-tab" class="tab-content">
            <div id="subscription-info"></div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            
            if (tabName === 'requests') {
                loadRequests();
            } else if (tabName === 'assigned') {
                loadAssigned();
            } else if (tabName === 'completed') {
                loadCompleted();
            } else if (tabName === 'subscription') {
                loadSubscription();
            }
        }
        
        async function loadRequests() {
            try {
                const response = await fetch('php/get_worker_jobs.php', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('requests-list');
                    const requested = data.data.filter(job => job.status === 'requested');
                    
                    if (requested.length === 0) {
                        container.innerHTML = '<p style="text-align: center; padding: 40px;">No has solicitado ningún trabajo aún.</p>';
                        return;
                    }
                    
                    container.innerHTML = requested.map(job => {
                        const statusText = {
                            'requested': 'Pendiente',
                            'selected': 'Seleccionado',
                            'rejected': 'Rechazado'
                        }[job.status] || job.status;
                        
                        return `
                            <div class="job-card-dashboard">
                                <div class="job-card-header">
                                    <div>
                                        <h2>${job.title}</h2>
                                        <p>${job.category_name || ''} - ${job.subcategory_name || ''}</p>
                                        <p><i class='bx bx-time'></i> ${job.duration_hours} horas | <i class='bx bx-map'></i> ${job.city || ''}, ${job.province || ''}</p>
                                        <p><strong>Empleador:</strong> ${job.employer_name || ''} ${job.employer_last_name || ''}</p>
                                    </div>
                                    <span class="response-status status-${job.status}">${statusText}</span>
                                </div>
                                <p>${job.description}</p>
                                <p style="font-size: 12px; color: #999;">Solicitado: ${new Date(job.created_at).toLocaleString()}</p>
                                ${job.message ? `<p style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-style: italic;">Tu mensaje: ${job.message}</p>` : ''}
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function loadAssigned() {
            try {
                const response = await fetch('php/get_worker_jobs.php', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('assigned-list');
                    const assigned = data.data.filter(job => job.assignment_status && (job.assignment_status === 'assigned' || job.assignment_status === 'in_progress'));
                    
                    if (assigned.length === 0) {
                        container.innerHTML = '<p style="text-align: center; padding: 40px;">No tienes trabajos asignados.</p>';
                        return;
                    }
                    
                    container.innerHTML = assigned.map(job => {
                        const statusText = {
                            'assigned': 'Asignado',
                            'in_progress': 'En Progreso',
                            'pending_completion': 'Pendiente Confirmación',
                            'disputed': 'En Disputa'
                        }[job.assignment_status] || job.assignment_status;
                        
                        const isPending = job.assignment_status === 'pending_completion';
                        const isDisputed = job.assignment_status === 'disputed';
                        
                        return `
                            <div class="job-card-dashboard">
                                <div class="job-card-header">
                                    <div>
                                        <h2>${job.title}</h2>
                                        <p>${job.category_name || ''} - ${job.subcategory_name || ''}</p>
                                        <p><i class='bx bx-time'></i> ${job.duration_hours} horas | <i class='bx bx-map'></i> ${job.city || ''}, ${job.province || ''}</p>
                                        <p><strong>Empleador:</strong> ${job.employer_name || ''} ${job.employer_last_name || ''}</p>
                                    </div>
                                    <span class="response-status assignment-${job.assignment_status}">${statusText}</span>
                                </div>
                                <p>${job.description}</p>
                                <p style="font-size: 12px; color: #999;">Asignado: ${new Date(job.assigned_at).toLocaleString()}</p>
                                ${isPending ? `
                                <div style="background: #fff3e0; border: 1px solid #ff9800; border-radius: 8px; padding: 15px; margin-top: 15px;">
                                    <h4 style="color: #f57c00; margin: 0 0 10px 0;">⏳ Esperando Confirmación</h4>
                                    <p style="margin: 5px 0;">Has marcado este trabajo como completado. El empleador debe confirmar.</p>
                                    ${job.completed_at ? `<p style="font-size: 12px; color: #666;">Completado el: ${new Date(job.completed_at).toLocaleString()}</p>` : ''}
                                </div>
                                ` : ''}
                                ${isDisputed ? `
                                <div style="background: #ffebee; border: 1px solid #f44336; border-radius: 8px; padding: 15px; margin-top: 15px;">
                                    <h4 style="color: #c62828; margin: 0 0 10px 0;">⚠️ Proceso de Verificación</h4>
                                    <p style="margin: 5px 0;">El empleador ha rechazado la finalización. Hay un proceso de verificación activo.</p>
                                    <button class="btn-chat" onclick="viewDispute(${job.task_id})" style="margin-top: 10px; background: #f44336;">Ver Disputa</button>
                                </div>
                                ` : ''}
                                ${job.assignment_status === 'assigned' || job.assignment_status === 'in_progress' ? `
                                <div class="contact-info">
                                    <h4>📞 Información de Contacto del Empleador</h4>
                                    <p><i class='bx bx-user'></i><strong>Nombre:</strong> ${job.employer_name || ''} ${job.employer_last_name || ''}</p>
                                    <p><i class='bx bx-envelope'></i><strong>Email:</strong> <a href="mailto:${job.employer_email || ''}" style="color: #1976d2; text-decoration: none; font-weight: bold;">${job.employer_email || 'No disponible'}</a></p>
                                    ${job.employer_phone ? `<p><i class='bx bx-phone'></i><strong>Teléfono:</strong> <a href="tel:${job.employer_phone}" style="color: #1976d2; text-decoration: none; font-weight: bold;">${job.employer_phone}</a></p>` : '<p style="color: #999; font-style: italic;">Teléfono no disponible</p>'}
                                </div>
                                ` : ''}
                                ${!isPending && !isDisputed ? `
                                <div class="job-actions">
                                    <button class="btn-complete" onclick="completeJob(${job.task_id})">Marcar como Completado</button>
                                </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function loadCompleted() {
            try {
                const response = await fetch('php/get_worker_jobs.php', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('completed-list');
                    const completed = data.data.filter(job => job.assignment_status === 'done');
                    
                    if (completed.length === 0) {
                        container.innerHTML = '<p style="text-align: center; padding: 40px;">No has completado ningún trabajo aún.</p>';
                        return;
                    }
                    
                    container.innerHTML = completed.map(job => {
                        return `
                            <div class="job-card-dashboard">
                                <div class="job-card-header">
                                    <div>
                                        <h2>${job.title}</h2>
                                        <p>${job.category_name || ''} - ${job.subcategory_name || ''}</p>
                                        <p><i class='bx bx-time'></i> ${job.duration_hours} horas | <i class='bx bx-map'></i> ${job.city || ''}, ${job.province || ''}</p>
                                        <p><strong>Empleador:</strong> ${job.employer_name || ''} ${job.employer_last_name || ''}</p>
                                    </div>
                                    <span class="response-status assignment-done">Completado</span>
                                </div>
                                <p>${job.description}</p>
                                <p style="font-size: 12px; color: #999;">Completado: ${job.completed_at ? new Date(job.completed_at).toLocaleString() : 'N/A'}</p>
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function completeJob(taskId) {
            if (!confirm('¿Estás seguro de que completaste este trabajo?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('task_id', taskId);
                
                const response = await fetch('php/complete_job.php', {
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
                alert('Error al completar el trabajo');
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
                                <h3>Tu Mensaje:</h3>
                                <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;">
                                    <p>${dispute.worker_message || 'Sin mensaje'}</p>
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <h3>Mensaje del Empleador:</h3>
                                <div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 10px 0;">
                                    <p>${dispute.employer_message || 'Sin mensaje del empleador'}</p>
                                </div>
                            </div>
                            <div id="alert-dispute" style="display: none; padding: 15px; margin: 15px 0; border-radius: 5px;"></div>
                            <div style="margin-top: 20px;">
                                <h4>Agregar Mensaje:</h4>
                                <textarea id="dispute-message" placeholder="Escribe tu argumento..." style="width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin: 10px 0;"></textarea>
                                <button class="btn-chat" onclick="addDisputeMessage(${taskId})" style="background: #2196f3;">Enviar Mensaje</button>
                            </div>
                            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                                <p><strong>💡 Consejo:</strong> Contacta al empleador directamente mediante email o teléfono para resolver la disputa más rápidamente.</p>
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
        
        async function loadSubscription() {
            try {
                const response = await fetch('php/get_subscription_info.php', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('subscription-info');
                    const sub = data.subscription;
                    const stats = data.job_stats;
                    
                    if (data.has_subscription && sub) {
                        const expiresDate = new Date(sub.expires_at);
                        const isActive = sub.status === 'active';
                        const statusText = isActive ? 'Activa' : 'Cancelada';
                        const statusColor = isActive ? '#4caf50' : '#ff9800';
                        const headerBg = isActive 
                            ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' 
                            : 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)';
                        const headerTitle = isActive 
                            ? '✨ Suscripción Premium Activa' 
                            : '⚠️ Suscripción Cancelada (Válida hasta vencimiento)';
                        const headerSubtitle = isActive 
                            ? 'Puedes tomar trabajos sin límite' 
                            : 'Podrás usar los beneficios hasta la fecha de vencimiento';
                        
                        container.innerHTML = `
                            <div style="background: ${headerBg}; color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                                <h2 style="margin: 0 0 10px 0;">${headerTitle}</h2>
                                <p style="margin: 5px 0; font-size: 18px;">${headerSubtitle}</p>
                            </div>
                            
                            <div class="job-card-dashboard">
                                <h3>📋 Información de tu Suscripción</h3>
                                <div style="margin: 20px 0;">
                                    <p><strong>Plan:</strong> Premium Trabajador</p>
                                    <p><strong>Estado:</strong> <span style="color: ${statusColor}; font-weight: bold;">${statusText}</span></p>
                                    <p><strong>Fecha de Inicio:</strong> ${new Date(sub.started_at).toLocaleDateString('es-AR')}</p>
                                    <p><strong>Fecha de Vencimiento:</strong> ${expiresDate.toLocaleDateString('es-AR')}</p>
                                    <p><strong>Días Restantes:</strong> <span style="color: ${sub.days_remaining <= 7 ? '#f44336' : '#4caf50'}; font-weight: bold;">${sub.days_remaining} días</span></p>
                                    ${sub.price ? `<p><strong>Precio:</strong> $${parseFloat(sub.price).toLocaleString('es-AR')} ${sub.currency || 'ARS'}</p>` : ''}
                                </div>
                                ${isActive ? `
                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                    <button class="btn-complete" onclick="renewSubscription()" style="background: #667eea; flex: 1;">
                                        Renovar Suscripción
                                    </button>
                                    <button class="btn-complete" onclick="cancelSubscription()" style="background: #f44336; flex: 1;">
                                        Cancelar Suscripción
                                    </button>
                                </div>
                                ` : `
                                <div style="margin-top: 15px;">
                                    <button class="btn-complete" onclick="renewSubscription()" style="background: #667eea; width: 100%;">
                                        Reactivar Suscripción
                                    </button>
                                </div>
                                `}
                            </div>
                            
                            <div class="job-card-dashboard" style="background: #e8f5e9; border: 2px solid #4caf50;">
                                <h3>✅ Beneficios de tu Suscripción</h3>
                                <ul style="line-height: 2;">
                                    <li>✅ Trabajos ilimitados sin restricciones</li>
                                    <li>✅ Sin límite de solicitudes cada 5 días</li>
                                    <li>✅ Acceso completo a todas las funciones</li>
                                </ul>
                            </div>
                        `;
                    } else {
                        container.innerHTML = `
                            <div style="background: #fff3e0; border: 2px solid #ff9800; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                                <h2 style="margin: 0 0 10px 0; color: #f57c00;">⚠️ Sin Suscripción Activa</h2>
                                <p style="margin: 5px 0; font-size: 16px;">Actualmente estás en el plan gratuito con límites</p>
                            </div>
                            
                            <div class="job-card-dashboard">
                                <h3>📊 Tu Estado Actual</h3>
                                <div style="margin: 20px 0;">
                                    <p><strong>Trabajos tomados (últimos 5 días):</strong> ${stats.jobs_taken} de ${stats.limit}</p>
                                    <p><strong>Trabajos restantes:</strong> <span style="color: ${stats.remaining === 0 ? '#f44336' : '#4caf50'}; font-weight: bold;">${stats.remaining}</span></p>
                                    ${stats.remaining === 0 ? '<p style="color: #f44336; font-weight: bold;">⚠️ Has alcanzado el límite. Suscríbete para tomar más trabajos.</p>' : ''}
                                </div>
                            </div>
                            
                            <div class="job-card-dashboard" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <h2 style="margin: 0 0 20px 0;">🚀 Suscríbete a Premium</h2>
                                <div style="margin: 20px 0;">
                                    <h3 style="font-size: 36px; margin: 10px 0;">$${data.subscription_price.toLocaleString('es-AR')} <span style="font-size: 18px;">ARS/mes</span></h3>
                                    <ul style="line-height: 2.5; margin: 20px 0;">
                                        <li>✅ Trabajos ilimitados</li>
                                        <li>✅ Sin restricciones de tiempo</li>
                                        <li>✅ Acceso prioritario a nuevas ofertas</li>
                                        <li>✅ Soporte prioritario</li>
                                    </ul>
                                </div>
                                <button class="btn-complete" onclick="createSubscription()" style="background: white; color: #667eea; font-size: 18px; padding: 15px 40px;">
                                    Suscribirme Ahora
                                </button>
                                <p style="margin-top: 15px; font-size: 12px; opacity: 0.9;">* La suscripción se renueva automáticamente cada mes</p>
                            </div>
                            
                            <div class="job-card-dashboard">
                                <h3>💡 Plan Gratuito vs Premium</h3>
                                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                                    <thead>
                                        <tr style="background: #f5f5f5;">
                                            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Característica</th>
                                            <th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Gratuito</th>
                                            <th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Premium</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="padding: 10px; border-bottom: 1px solid #eee;">Trabajos cada 5 días</td>
                                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;">2</td>
                                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee; color: #4caf50; font-weight: bold;">Ilimitados</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; border-bottom: 1px solid #eee;">Restricciones de tiempo</td>
                                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;">❌</td>
                                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee; color: #4caf50; font-weight: bold;">✅</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px;">Soporte</td>
                                            <td style="padding: 10px; text-align: center;">Estándar</td>
                                            <td style="padding: 10px; text-align: center; color: #4caf50; font-weight: bold;">Prioritario</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('subscription-info').innerHTML = '<p style="text-align: center; padding: 40px; color: #f44336;">Error al cargar la información de suscripción.</p>';
            }
        }
        
        async function createSubscription() {
            if (!confirm('¿Deseas suscribirte por $15,000 ARS/mes? Esto te permitirá tomar trabajos sin límite.')) {
                return;
            }
            
            try {
                const response = await fetch('php/create_subscription.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadSubscription();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al crear la suscripción');
            }
        }
        
        async function renewSubscription() {
            if (!confirm('¿Deseas renovar tu suscripción por $15,000 ARS/mes?')) {
                return;
            }
            
            try {
                const response = await fetch('php/create_subscription.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadSubscription();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al renovar la suscripción');
            }
        }
        
        async function cancelSubscription() {
            if (!confirm('¿Estás seguro de que deseas cancelar tu suscripción?\n\nPodrás seguir usando los beneficios hasta la fecha de vencimiento, pero no se renovará automáticamente.')) {
                return;
            }
            
            try {
                const response = await fetch('php/cancel_subscription.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadSubscription();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cancelar la suscripción');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadRequests();
        });
    </script>
</body>
</html>

