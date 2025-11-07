<?php
include "php/session_check.php";
include "php/connection.php";

$sql = "SELECT t.*, tc.name as category_name, sc.name as subcategory_name, l.city, l.province 
        FROM tasks t 
        LEFT JOIN task_categories tc ON t.category_id = tc.id 
        LEFT JOIN sub_categories sc ON t.sub_categories_id = sc.id
        LEFT JOIN locations l ON t.location_id = l.id 
        WHERE t.status = 'published' 
        ORDER BY t.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/jobs.css">
    <title>Trabajos</title>
</head>
<body>
    <header>
        <i class='bx bxs-home-alt-2 bx-lg'  onclick="window.location.href='index.php'"></i>
    
    </header>

    <h1 class="title-jobs">trabajos</h1>

        <section id="jobs">
        <?php if ($result->num_rows > 0): ?>
            <?php 
            $user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;
            $applied_jobs = [];
            if ($user_id) {
                $applied_sql = "SELECT task_id FROM task_responses WHERE worker_id = ? AND status != 'withdrawn'";
                $applied_stmt = $conn->prepare($applied_sql);
                $applied_stmt->bind_param("i", $user_id);
                $applied_stmt->execute();
                $applied_result = $applied_stmt->get_result();
                while ($applied_row = $applied_result->fetch_assoc()) {
                    $applied_jobs[] = $applied_row['task_id'];
                }
                $applied_stmt->close();
            }
            ?>
            <?php while($row = $result->fetch_assoc()): 
                $isApplied = in_array($row['id'], $applied_jobs);
            ?>
                <div class="jobs-card c1" data-job-id="<?php echo $row['id']; ?>">
                    <div class="job-image">
                        <i class='bx bx-mountain'></i>
                    </div>
                    <div class="job-title">
                        <p><?php echo htmlspecialchars($row["title"]); ?></p>
                        <small><?php echo htmlspecialchars($row["category_name"] ?? "Sin categoría"); ?> - <?php echo htmlspecialchars($row["subcategory_name"] ?? ""); ?></small>
                    </div>
                    <div class="job-info">
                        <p><i class='bx bx-time'></i> <?php echo htmlspecialchars($row["duration_hours"]); ?> horas</p>
                        <p><i class='bx bx-map'></i> <?php echo htmlspecialchars($row["city"] ?? ""); ?>, <?php echo htmlspecialchars($row["province"] ?? ""); ?></p>
                        <?php if ($row["scheduled_at"]): ?>
                            <p><i class='bx bx-calendar'></i> <?php echo date('d/m/Y H:i', strtotime($row["scheduled_at"])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="job-actions">
                        <button class="view-details-btn" onclick="viewDetails(<?php echo $row['id']; ?>)">Ver Detalles</button>
                        <?php if ($isApplied): ?>
                            <button class="take-job-btn applied" id="btn-take-<?php echo $row['id']; ?>" disabled>Solicitado</button>
                        <?php else: ?>
                            <button class="take-job-btn" onclick="takeJob(<?php echo $row['id']; ?>)" id="btn-take-<?php echo $row['id']; ?>">Tomar Trabajo</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-jobs">
                <p>No hay trabajos disponibles en este momento.</p>
            </div>
        <?php endif; ?>

        </section>

        <footer>
            <div class="container-footer-information f1">

                <dl>

                    <dt>titulo</dt>
                    <hr>

                    <dd>elemento 1</dd>
                    <dd>elemento 2</dd>
                    <dd>elemento 3</dd>
                    <dd>elemento 4</dd>

                </dl>

            </div>
            <div class="container-footer-information f2">

                <dl>

                    <dt>titulo</dt>
                    <hr>
                  
                    <dd>elemento 1</dd>
                    <dd>elemento 2</dd>

                </dl>

            </div>
            <div class="container-footer-networks f3">

                <dl>

                    <dt>REDES</dt>
                    <hr>

                    <div class="container-networks-icons">

                        <i class='bx bxl-instagram'></i>
                        <i class='bx bxl-discord-alt' ></i>                        
                        <i class='bx bx-link-alt' ></i>     

                    </div>

                </dl>

            </div>

        </footer>

<div id="jobModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div id="modal-body">
            <p>Cargando...</p>
        </div>
    </div>
</div>

<script>
async function takeJobFromModal(jobId, message = '') {
    const btn = document.getElementById('btn-take-modal-' + jobId);
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Solicitando...';
    }
    
    try {
        const formData = new FormData();
        formData.append('task_id', jobId);
        if (message) {
            formData.append('message', message);
        }
        
        const response = await fetch('php/take_job.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeModal();
            
            const cardBtn = document.getElementById('btn-take-' + jobId);
            if (cardBtn) {
                cardBtn.textContent = 'Solicitado';
                cardBtn.disabled = true;
                cardBtn.classList.add('applied');
            }
            
            showAlert(data.message, 'success');
        } else {
            if (data.limit_reached) {
                showLimitReachedAlert(data.message, data.jobs_taken, data.limit);
            } else {
                showAlert(data.message, 'error');
            }
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Solicitar Trabajo';
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión. Por favor intenta nuevamente.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Solicitar Trabajo';
        }
    }
}

async function takeJob(jobId) {
    await viewDetails(jobId);
    
    setTimeout(() => {
        const modalContent = document.querySelector('.modal-content');
        if (modalContent) {
            const requestForm = modalContent.querySelector('.request-form');
            if (requestForm) {
                requestForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }, 300);
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(alertDiv);
        }, 300);
    }, 4000);
}

function showLimitReachedAlert(message, jobsTaken, limit) {
    const alertDiv = document.createElement('div');
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 20px;
        background: #fff3e0;
        border: 2px solid #ff9800;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;
    
    alertDiv.innerHTML = `
        <div style="margin-bottom: 15px;">
            <h3 style="margin: 0 0 10px 0; color: #f57c00;">⚠️ Límite Alcanzado</h3>
            <p style="margin: 5px 0; color: #333;">${message}</p>
            <p style="margin: 5px 0; font-size: 14px; color: #666;">Has tomado ${jobsTaken} de ${limit} trabajos en los últimos 5 días.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.location.href='worker_dashboard.php#subscription'" 
                    style="flex: 1; padding: 10px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                Ver Suscripción
            </button>
            <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                    style="padding: 10px 15px; background: #ccc; color: #333; border: none; border-radius: 5px; cursor: pointer;">
                Cerrar
            </button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            if (document.body.contains(alertDiv)) {
                document.body.removeChild(alertDiv);
            }
        }, 300);
    }, 8000);
}

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

async function viewDetails(jobId) {
    const modal = document.getElementById('jobModal');
    const modalBody = document.getElementById('modal-body');
    
    modal.style.display = 'block';
    modalBody.innerHTML = '<p>Cargando detalles...</p>';
    
    try {
        const response = await fetch(`php/get_job_details.php?task_id=${jobId}`, {
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const job = data.data;
            const hasApplied = data.has_applied;
            
            let html = `
                <div style="border-bottom: 2px solid #e0e0e0; padding-bottom: 20px; margin-bottom: 20px;">
                    <h2 style="margin: 0 0 10px 0; color: #333;">${job.title || 'Sin título'}</h2>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                        <span style="background: #e3f2fd; color: #1976d2; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                            <i class='bx bx-category'></i> ${job.category_name || 'Sin categoría'}${job.subcategory_name ? ' - ' + job.subcategory_name : ''}
                        </span>
                        <span style="background: #fff3e0; color: #f57c00; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                            <i class='bx bx-time'></i> ${job.duration_hours || 0} horas
                        </span>
                    </div>
                </div>
                
                <div class="job-details-section">
                    <h3><i class='bx bx-file-blank'></i> Descripción del Trabajo</h3>
                    <p style="white-space: pre-wrap; line-height: 1.6;">${job.description || 'Sin descripción'}</p>
                </div>
                
                <div class="job-details-section">
                    <h3><i class='bx bx-info-circle'></i> Información Detallada</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Categoría:</strong>
                            <span>${job.category_name || 'Sin categoría'}</span>
                        </div>
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Sub-categoría:</strong>
                            <span>${job.subcategory_name || 'Sin sub-categoría'}</span>
                        </div>
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Duración Estimada:</strong>
                            <span>${job.duration_hours || 0} horas</span>
                        </div>
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Provincia:</strong>
                            <span>${job.province || 'No especificada'}</span>
                        </div>
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Ciudad:</strong>
                            <span>${job.city || 'No especificada'}</span>
                        </div>
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Dirección:</strong>
                            <span>${job.address_text || 'No especificada'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="job-details-section" style="background: #f5f5f5; border-left: 4px solid #2196F3;">
                    <h3><i class='bx bx-user'></i> Información del Empleador</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Nombre:</strong>
                            <span>${job.employer_name || ''} ${job.employer_last_name || ''}</span>
                        </div>
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Email:</strong>
                            <a href="mailto:${job.employer_email || ''}" style="color: #2196F3; text-decoration: none;">${job.employer_email || 'No disponible'}</a>
                        </div>
                        ${job.employer_phone ? `
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Teléfono:</strong>
                            <a href="tel:${job.employer_phone}" style="color: #2196F3; text-decoration: none;">${job.employer_phone}</a>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            if (job.scheduled_at) {
                html += `
                    <div class="job-details-section">
                        <h3>Fecha Programada</h3>
                        <p><strong>Fecha y Hora:</strong> ${new Date(job.scheduled_at).toLocaleString('es-ES')}</p>
                    </div>
                `;
            }
            
            if (!hasApplied) {
                html += `
                    <div class="job-details-actions request-form">
                        <h3 style="margin-bottom: 15px;">Solicitar este Trabajo</h3>
                        <div style="margin-bottom: 15px;">
                            <label for="request-message-${jobId}" style="display: block; margin-bottom: 5px; font-weight: bold;">Mensaje opcional para el empleador:</label>
                            <textarea id="request-message-${jobId}" placeholder="Escribe un mensaje personalizado (opcional)..." style="width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; resize: vertical; max-length: 240;"></textarea>
                            <small style="color: #666; display: block; margin-top: 5px;">Máximo 240 caracteres</small>
                        </div>
                        <button class="take-job-btn" id="btn-take-modal-${jobId}" onclick="takeJobFromModal(${jobId}, document.getElementById('request-message-${jobId}').value.trim())">
                            <i class='bx bx-check-circle'></i> Solicitar Trabajo
                        </button>
                    </div>
                `;
            } else {
                html += `
                    <div class="job-details-actions">
                        <div style="background: #e8f5e9; border: 2px solid #4CAF50; border-radius: 8px; padding: 15px; text-align: center;">
                            <i class='bx bx-check-circle' style="font-size: 48px; color: #4CAF50; display: block; margin-bottom: 10px;"></i>
                            <p style="color: #2e7d32; font-weight: bold; margin: 0;">Ya has solicitado este trabajo</p>
                            <p style="color: #666; font-size: 14px; margin-top: 5px;">El empleador revisará tu solicitud</p>
                        </div>
                    </div>
                `;
            }
            
            modalBody.innerHTML = html;
        } else {
            modalBody.innerHTML = `<p style="color: red;">${data.message}</p>`;
        }
    } catch (error) {
        console.error('Error:', error);
        modalBody.innerHTML = '<p style="color: red;">Error al cargar los detalles del trabajo.</p>';
    }
}

function closeModal() {
    document.getElementById('jobModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('jobModal');
    if (event.target == modal) {
        closeModal();
    }
}

</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    border-radius: 10px;
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.close-modal {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover,
.close-modal:focus {
    color: black;
}

.job-details-section {
    margin: 20px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.job-details-section h3 {
    margin-top: 0;
    color: #333;
}

.job-details-actions {
    margin-top: 20px;
    text-align: center;
}

.job-info {
    margin: 10px 0;
    font-size: 14px;
    color: #666;
}

.job-info p {
    margin: 5px 0;
}
</style>

</body>
</html>
