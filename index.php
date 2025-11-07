<?php
include "php/session_check.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <title>Página Principal</title>
</head>
<body>
    
    <header>
        <ul class="elements-header">
            <li><a href="#">Contactános</a></li>
            <li><a href="#information">Sobre Nosotros</a></li>
        </ul>

        <div class="buttons-login">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION["user_name"] ?? "Usuario"); ?>!</span>
            <a id="button-login" href="employer_dashboard.php" style="margin-right: 10px;">Panel Empleador</a>
            <a id="button-login" href="worker_dashboard.php" style="margin-right: 10px;">Panel Trabajador</a>
            <a id="button-login" href="php/logout.php">Cerrar Sesión</a>
        </div>
        
    </header>

        <section class="buttons-principal-page">
            <div class="button button-post" onclick="window.location.href='jobs.php'">
                <a href="jobs.php">Ver trabajos</a>
            </div>
    
            <div class="button button-post" onclick="window.location.href='form-jobs.html'">
                <a href="form-jobs.html">Publicar trabajo</a>
            </div>
    
        </section>

    <h1 class="title-information">información</h1>
        
        <section id="information">
            <div class="information-card i1">

                <div class="container-img">
                    <img src="img/prueba-3.webp" alt="img">
                </div>

                <p class="information-text">Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia esse qui sequi! 
                    Nisi adipisci qui culpa ipsum debitis libero consequuntur earum officia quaerat velit id, 
                    eius molestiae? Facere, labore iste. Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia esse qui sequi! 
                    Nisi adipisci qui culpa ipsum debitis libero consequuntur earum officia quaerat velit id, 
                    eius molestiae? Facere, labore iste. Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia esse qui sequi! 
                    Nisi adipisci qui culpa ipsum debitis libero consequuntur earum officia quaerat velit id, 
                    eius molestiae? Facere, labore iste.
                </p>

            </div>

            <div class="information-card i2">

                <div class="container-img">
                    <img src="img/prueba-3.webp" alt="img">
                </div>

                <p class="information-text">Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia esse qui sequi! 
                    Nisi adipisci qui culpa ipsum debitis libero consequuntur earum officia quaerat velit id, 
                    eius molestiae? Facere, labore iste. Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia esse qui sequi! 
                    Nisi adipisci qui culpa ipsum debitis libero consequuntur earum officia quaerat velit id, 
                    eius molestiae? Facere, labore iste. Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia esse qui sequi! 
                    Nisi adipisci qui culpa ipsum debitis libero consequuntur earum officia quaerat velit id, 
                    eius molestiae? Facere, labore iste.
                </p>

            </div>

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

</body>

</html>
