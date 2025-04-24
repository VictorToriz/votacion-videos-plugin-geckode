<?php
/**
 * Plugin Name: Concurso Videos Geckode
 * Description: Sistema de concurso de videos con votación
 * Version: 3.0
 * Author: Geckode
 * Author URI: https://geckode.com.mx
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// HOOKS DE ACTIVACIÓN Y DESINSTALACIÓN
register_activation_hook(__FILE__, 'honeywhale_activacion');
register_uninstall_hook(__FILE__, 'honeywhale_desinstalar');

// FUNCIONES PRINCIPALES

/**
 * Función de activación del plugin
 */
function honeywhale_activacion() {
    // Establecer valores por defecto
    if (!get_option('hw_concurso_fecha_fin')) {
        // Fecha predeterminada: 1 mes desde hoy
        $fecha_predeterminada = date('Y-m-d', strtotime('+1 month'));
        update_option('hw_concurso_fecha_fin', $fecha_predeterminada);
    }
    
    // Título por defecto si no existe
    if (!get_option('hw_concurso_titulo')) {
        update_option('hw_concurso_titulo', 'Concurso de Videos HoneyWhale');
    }
    
    // Descripción por defecto
    if (!get_option('hw_concurso_descripcion')) {
        update_option('hw_concurso_descripcion', 'Vota por tus videos favoritos. La votación termina en:');
    }
    
    // Número de videos por defecto
    if (!get_option('hw_max_videos')) {
        update_option('hw_max_videos', 6);
    }
    
    // Establecer valores por defecto para los videos
    $videos_default = array(
        1 => array(
            'url' => 'https://honeywhale.com.mx/wp-content/uploads/2025/03/Video-HW-Walmart-1280x720-720dpi.mp4',
            'titulo' => 'Promo Walmart',
            'autor' => 'Ana Metralla'
        ),
        2 => array(
            'url' => 'https://honeywhale.com.mx/wp-content/uploads/2024/07/Honey-Whale-Scooter-M1.mp4',
            'titulo' => 'Scooter M1 Honey Whale',
            'autor' => 'Niña rara'
        ),
        3 => array(
            'url' => 'https://honeywhale.com.mx/wp-content/uploads/2024/02/Honey-Whale-Scooter-H4.mp4',
            'titulo' => 'Review Scooter H4',
            'autor' => 'El Pollo'
        )
    );
    
    foreach ($videos_default as $id => $video) {
        if (!get_option('hw_video_url_'.$id)) {
            update_option('hw_video_url_'.$id, $video['url']);
            update_option('hw_video_titulo_'.$id, $video['titulo']);
            update_option('hw_video_autor_'.$id, $video['autor']);
        }
    }
    
    // Para videos 4-6, establecer valores vacíos si no existen
    for ($i = 4; $i <= 6; $i++) {
        if (!get_option('hw_video_url_'.$i)) {
            update_option('hw_video_url_'.$i, 'URL_VIDEO_'.$i.'_AQUÍ');
            update_option('hw_video_titulo_'.$i, 'TÍTULO REAL DEL VIDEO '.$i);
            update_option('hw_video_autor_'.$i, 'NOMBRE DEL AUTOR '.$i);
        }
    }
}

/**
 * Función para desinstalar el plugin
 */
function honeywhale_desinstalar() {
    global $wpdb;
    // Eliminar todas las opciones relacionadas con el plugin
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hw_votos_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hw_video_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hw_ip_votantes_%'");
    delete_option('hw_concurso_fecha_fin');
    delete_option('hw_concurso_titulo');
    delete_option('hw_concurso_descripcion');
    delete_option('hw_max_videos');
    
    // Eliminar el archivo de log si existe
    $log_file = WP_CONTENT_DIR . '/uploads/hw-votos-log.txt';
    if (file_exists($log_file)) {
        unlink($log_file);
    }
}

/**
 * Función principal para mostrar la galería de videos
 */
function honeywhale_concurso_perfecto() {
    // Iniciar buffer de salida
    ob_start(); 
    
    // Verificar si el usuario ya votó
    $user_ip = honeywhale_get_client_ip();
    $ha_votado = false;
    $video_votado = 0;
    
    // Comprobar si esta IP ya ha votado por algún video
    $max_videos = get_option('hw_max_videos', 6);
    for ($i = 1; $i <= $max_videos; $i++) {
        $ip_votantes = get_option('hw_ip_votantes_' . $i, array());
        if (in_array($user_ip, $ip_votantes)) {
            $ha_votado = true;
            $video_votado = $i;
            break;
        }
    }
    
    // Cargar CSS y JS necesarios
    wp_enqueue_style('hw-google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800&display=swap');
    wp_enqueue_style('hw-animate', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');
    ?>
    
    <style>
        .hw-video-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2.5rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .hw-video-card {
            background: #f8f8f8;
            border: 2px solid #000;
            border-radius: 12px;
            padding: 1.75rem;
            text-align: center;
            transition: all 0.4s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .hw-video-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .hw-video-title {
            font-family: 'Pangram', 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 22px;
            text-transform: uppercase;
            color: #000;
            margin: 1.2rem 0 0.5rem 0;
            line-height: 1.3;
        }
        
        .hw-video-author {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            color: #555;
            margin-bottom: 1.2rem;
            font-style: italic;
        }
        
        .hw-video-player { 
            width: 100%; 
            border-radius: 10px; 
            margin-bottom: 1.2rem; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            aspect-ratio: 16/9;
            background-color: #000;
        }
        
        .hw-vote-btn {
            background: #ffd219;
            border: 2px solid #000;
            border-radius: 30px;
            padding: 0.9rem 2.2rem;
            font-family: 'Pangram', 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 150px;
        }
        
        .hw-vote-btn:hover { 
            background: #ffda3e; 
            transform: scale(1.05);
        }
        
        .hw-vote-btn:active {
            transform: scale(0.98);
        }
        
        .hw-vote-btn:disabled { 
            background: #d1d1d1; 
            border-color: #999; 
            cursor: not-allowed; 
            transform: none;
        }
        
        .hw-vote-count {
            font-family: 'Pangram', 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 18px;
            margin-top: 1.2rem;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .hw-vote-count svg {
            color: #ff4757;
        }
        
        .hw-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff4757;
            color: white;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 14px;
            padding: 5px 12px;
            border-radius: 30px;
            z-index: 2;
            box-shadow: 0 3px 8px rgba(255, 71, 87, 0.3);
        }
        
        .hw-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: hw-spin 1s ease-in-out infinite;
            margin-left: 8px;
        }
        
        @keyframes hw-spin {
            to { transform: rotate(360deg); }
        }
        
        .hw-winner {
            background: linear-gradient(135deg, #fff6e5, #fffbf2);
            border: 2px solid #ffc107;
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.2);
        }
        
        .hw-winner::before {
            content: "🏆";
            position: absolute;
            top: -10px;
            left: -10px;
            font-size: 30px;
            z-index: 2;
        }
        
        .hw-videos-filter {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hw-filter-btn {
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 0.5rem 1.5rem;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .hw-filter-btn:hover,
        .hw-filter-btn.active {
            background: #000;
            color: #fff;
            border-color: #000;
        }
        
        .hw-contest-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 0 1rem;
        }
        
        .hw-contest-title {
            font-family: 'Pangram', 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 36px;
            margin-bottom: 1rem;
        }
        
        .hw-contest-desc {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            color: #555;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hw-contest-counter {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .hw-counter-item {
            background: #000;
            color: #fff;
            border-radius: 8px;
            padding: 1rem;
            min-width: 80px;
            text-align: center;
        }
        
        .hw-counter-number {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 28px;
        }
        
        .hw-counter-label {
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.7;
        }
        
        .hw-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .hw-modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            transform: translateY(-50px);
            transition: transform 0.4s ease;
            text-align: center;
        }
        
        .hw-modal.active {
            display: block;
            opacity: 1;
        }
        
        .hw-modal.active .hw-modal-content {
            transform: translateY(0);
        }
        
        .hw-modal-title {
            font-family: 'Pangram', 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 24px;
            margin-bottom: 1rem;
        }
        
        .hw-modal-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            margin-bottom: 1.5rem;
        }
        
        .hw-modal-close {
            display: inline-block;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: 0.8rem 2rem;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .hw-modal-close:hover {
            background: #333;
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .hw-video-gallery {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                padding: 1rem;
                gap: 1.5rem;
            }
            
            .hw-video-card {
                padding: 1.25rem;
            }
            
            .hw-contest-title {
                font-size: 28px;
            }
        }
    </style>

    <div class="hw-contest-container">
        <div class="hw-contest-header">
            <h2 class="hw-contest-title"><?php echo esc_html(get_option('hw_concurso_titulo', 'Concurso de Videos HoneyWhale')); ?></h2>
            <p class="hw-contest-desc"><?php echo esc_html(get_option('hw_concurso_descripcion', 'Vota por tus videos favoritos. La votación termina en:')); ?></p>
            
            <div class="hw-contest-counter" id="hw-countdown">
                <div class="hw-counter-item">
                    <div class="hw-counter-number" id="hw-days">00</div>
                    <div class="hw-counter-label">Días</div>
                </div>
                <div class="hw-counter-item">
                    <div class="hw-counter-number" id="hw-hours">00</div>
                    <div class="hw-counter-label">Horas</div>
                </div>
                <div class="hw-counter-item">
                    <div class="hw-counter-number" id="hw-minutes">00</div>
                    <div class="hw-counter-label">Minutos</div>
                </div>
                <div class="hw-counter-item">
                    <div class="hw-counter-number" id="hw-seconds">00</div>
                    <div class="hw-counter-label">Segundos</div>
                </div>
            </div>
        </div>
        
        <div class="hw-videos-filter">
            <button class="hw-filter-btn active" data-filter="all">Todos</button>
            <button class="hw-filter-btn" data-filter="popular">Más votados</button>
            <button class="hw-filter-btn" data-filter="recent">Más recientes</button>
        </div>
        
        <div class="hw-video-gallery">
            <?php
            // Array con la información de los videos desde opciones
            $max_videos = get_option('hw_max_videos', 6);
            $videos = array();
            
            for ($i = 1; $i <= $max_videos; $i++) {
                $url = get_option('hw_video_url_'.$i, '');
                $titulo = get_option('hw_video_titulo_'.$i, '');
                $autor = get_option('hw_video_autor_'.$i, '');
                
                // Valores por defecto si no hay datos guardados
                if (empty($url)) {
                    if ($i == 1) {
                        $url = 'https://honeywhale.com.mx/wp-content/uploads/2025/03/Video-HW-Walmart-1280x720-720dpi.mp4';
                        $titulo = 'Promo Walmart';
                        $autor = 'Ana Metralla';
                    } elseif ($i == 2) {
                        $url = 'https://honeywhale.com.mx/wp-content/uploads/2024/07/Honey-Whale-Scooter-M1.mp4';
                        $titulo = 'Scooter M1 Honey Whale';
                        $autor = 'Niña rara';
                    } elseif ($i == 3) {
                        $url = 'https://honeywhale.com.mx/wp-content/uploads/2024/02/Honey-Whale-Scooter-H4.mp4';
                        $titulo = 'Review Scooter H4';
                        $autor = 'El Pollo';
                    } else {
                        $url = 'URL_VIDEO_'.$i.'_AQUÍ';
                        $titulo = 'TÍTULO REAL DEL VIDEO '.$i;
                        $autor = 'NOMBRE DEL AUTOR '.$i;
                    }
                }
                
                $videos[$i] = array(
                    'url' => $url,
                    'titulo' => $titulo,
                    'autor' => $autor,
                    'fecha' => date('Y-m-d', strtotime('-'.($i*30).' days')) // Fecha simulada
                );
            }
            
            // Conseguir los votos para cada video y verificar si el usuario ya votó
            $votos = array();
            $max_votos = 0;
            
            foreach ($videos as $id => $video) {
                $votos[$id] = (int) get_option("hw_votos_" . $id, 0);
                if ($votos[$id] > $max_votos) {
                    $max_votos = $votos[$id];
                }
            }
            
            // Mostrar los videos
            foreach ($videos as $id => $video) {
                $disabled = $ha_votado ? 'disabled' : '';
                $btn_text = ($ha_votado && $video_votado == $id) ? '❤️ ¡Gracias!' : '❤️ VOTAR';
                $winner_class = ($votos[$id] == $max_votos && $max_votos > 0) ? 'hw-winner' : '';
                ?>
                <div class="hw-video-card <?php echo $winner_class; ?>" 
                     data-video-id="<?php echo $id; ?>"
                     data-votes="<?php echo $votos[$id]; ?>"
                     data-date="<?php echo strtotime($video['fecha']); ?>">
                    
                    <?php if ($votos[$id] == $max_votos && $max_votos > 0): ?>
                        <div class="hw-badge animate__animated animate__pulse animate__infinite">Más votado</div>
                    <?php endif; ?>
                    
                    <video controls class="hw-video-player" src="<?php echo esc_url($video['url']); ?>"></video>
                    <h3 class="hw-video-title"><?php echo esc_html($video['titulo']); ?></h3>
                    <p class="hw-video-author">Por: <strong><?php echo esc_html($video['autor']); ?></strong></p>
                    <button class="hw-vote-btn" onclick="honeywhaleVotar(<?php echo $id; ?>)" <?php echo $disabled; ?>>
                        <?php echo $btn_text; ?>
                    </button>
                    <div class="hw-vote-count" id="votes-<?php echo $id; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <span><?php echo $votos[$id]; ?> votos</span>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    
    <!-- Modal de confirmación -->
    <div class="hw-modal" id="hw-vote-modal">
        <div class="hw-modal-content">
            <h3 class="hw-modal-title">¡Gracias por tu voto!</h3>
            <p class="hw-modal-text">Tu voto ha sido registrado correctamente. ¡Buena suerte a tu participante favorito!</p>
            <button class="hw-modal-close" onclick="closeModal()">Cerrar</button>
        </div>
    </div>

    <script>
        // Contador regresivo
        function updateCountdown() {
            const endDate = new Date('<?php echo get_option("hw_concurso_fecha_fin", "2025-05-31"); ?> 23:59:59').getTime();
            const now = new Date().getTime();
            const distance = endDate - now;
            
            if (distance < 0) {
                document.getElementById('hw-days').textContent = '00';
                document.getElementById('hw-hours').textContent = '00';
                document.getElementById('hw-minutes').textContent = '00';
                document.getElementById('hw-seconds').textContent = '00';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('hw-days').textContent = days < 10 ? `0${days}` : days;
            document.getElementById('hw-hours').textContent = hours < 10 ? `0${hours}` : hours;
            document.getElementById('hw-minutes').textContent = minutes < 10 ? `0${minutes}` : minutes;
            document.getElementById('hw-seconds').textContent = seconds < 10 ? `0${seconds}` : seconds;
        }
        
        // Iniciar el contador
        updateCountdown();
        setInterval(updateCountdown, 1000);
        
        // Función para votar
        function honeywhaleVotar(video_id) {
            const card = document.querySelector(`[data-video-id="${video_id}"]`);
            const btn = card.querySelector('.hw-vote-btn');
            const countDisplay = document.getElementById(`votes-${video_id}`);
            
            if (btn.disabled) {
                showModal('Ya has votado', 'Solo puedes votar por un video en todo el concurso.');
                return;
            }
            
            // Mostrar indicador de carga
            const originalText = btn.textContent;
            btn.innerHTML = 'Procesando <div class="hw-loading"></div>';
            btn.disabled = true;
            
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=honeywhale_votar&video_id=${video_id}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    countDisplay.querySelector('span').textContent = `${data.data.votos} votos`;
                    btn.textContent = '❤️ ¡Gracias!';
                    
                    // Deshabilitar todos los botones de votación
                    document.querySelectorAll('.hw-vote-btn').forEach(button => {
                        button.disabled = true;
                        if (button !== btn) {
                            button.textContent = '❤️ VOTAR';
                        }
                    });
                    
                    // Mostrar modal de agradecimiento
                    showModal('¡Gracias por tu voto!', '¡Tu voto ha sido registrado correctamente!');
                    
                    // Actualizar la clase del más votado si es necesario
                    actualizarMasVotado();
                } else {
                    btn.textContent = originalText;
                    btn.disabled = false;
                    showModal('Error', data.data.error || 'Ocurrió un error al procesar tu voto.');
                }
            })
            .catch(error => {
                btn.textContent = originalText;
                btn.disabled = false;
                showModal('Error', 'Error al procesar tu voto. Por favor intenta de nuevo.');
                console.error(error);
            });
        }
        
        // Función para actualizar el badge del más votado
        function actualizarMasVotado() {
            const cards = document.querySelectorAll('.hw-video-card');
            let maxVotes = 0;
            let maxVoteId = null;
            
            // Encontrar el video con más votos
            cards.forEach(card => {
                const votes = parseInt(card.getAttribute('data-votes'));
                if (votes > maxVotes) {
                    maxVotes = votes;
                    maxVoteId = card.getAttribute('data-video-id');
                }
            });
            
            // Quitar clase ganador y badge de todos
            cards.forEach(card => {
                card.classList.remove('hw-winner');
                const badge = card.querySelector('.hw-badge');
                if (badge) badge.remove();
            });
            
            // Añadir clase y badge al ganador
            if (maxVoteId && maxVotes > 0) {
                const winnerCard = document.querySelector(`[data-video-id="${maxVoteId}"]`);
                winnerCard.classList.add('hw-winner');
                
                const badge = document.createElement('div');
                badge.className = 'hw-badge animate__animated animate__pulse animate__infinite';
                badge.textContent = 'Más votado';
                winnerCard.prepend(badge);
            }
        }
        
        // Funciones para el modal
        function showModal(title, message) {
            const modal = document.getElementById('hw-vote-modal');
            const modalTitle = modal.querySelector('.hw-modal-title');
            const modalText = modal.querySelector('.hw-modal-text');
            
            modalTitle.textContent = title;
            modalText.textContent = message;
            
            modal.classList.add('active');
        }
        
        function closeModal() {
            const modal = document.getElementById('hw-vote-modal');
            modal.classList.remove('active');
        }
        
        // Filtrado de videos
        document.querySelectorAll('.hw-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Actualizar botones activos
                document.querySelectorAll('.hw-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const cards = document.querySelectorAll('.hw-video-card');
                
                // Ordenar según el filtro
                const gallery = document.querySelector('.hw-video-gallery');
                const cardsArray = Array.from(cards);
                
                if (filter === 'popular') {
                    cardsArray.sort((a, b) => {
                        return parseInt(b.getAttribute('data-votes')) - parseInt(a.getAttribute('data-votes'));
                    });
                } else if (filter === 'recent') {
                    cardsArray.sort((a, b) => {
                        return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
                    });
                }
                
                // Reordenar en el DOM
                cardsArray.forEach(card => gallery.appendChild(card));
            });
        });
        
        // Cerrar el modal haciendo clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('hw-vote-modal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('honeywhale_concurso', 'honeywhale_concurso_perfecto');

/**
 * Función para procesar votos mediante AJAX
 */
function honeywhale_votar_video() {
    if (!isset($_POST['video_id'])) {
        wp_send_json_error(['error' => 'ID no recibido']);
    }

    $video_id = intval($_POST['video_id']);
    $user_ip = honeywhale_get_client_ip();
    
    // Verificar si esta IP ya ha votado por cualquier video
    $ha_votado = false;
    $video_votado = 0;
    
    // Comprobar todos los videos para ver si la IP ya ha votado
    $max_videos = get_option('hw_max_videos', 6);
    for ($i = 1; $i <= $max_videos; $i++) {
        $ip_votantes = get_option('hw_ip_votantes_' . $i, array());
        
        if (in_array($user_ip, $ip_votantes)) {
            $ha_votado = true;
            $video_votado = $i;
            break;
        }
    }
    
    // Si ya votó, enviar error
    if ($ha_votado) {
        $titulo_video = get_option('hw_video_titulo_' . $video_votado, 'otro video');
        wp_send_json_error(['error' => 'Ya has emitido tu voto por "' . $titulo_video . '". Solo se permite un voto por IP en todo el concurso.']);
    }
    
    // Actualizar contador de votos
    $votos_actuales = (int) get_option('hw_votos_' . $video_id, 0);
    $nuevos_votos = $votos_actuales + 1;
    update_option('hw_votos_' . $video_id, $nuevos_votos);
    
    // Guardar IP del votante para este video específico
    $ip_votantes = get_option('hw_ip_votantes_' . $video_id, array());
    $ip_votantes[] = $user_ip;
    update_option('hw_ip_votantes_' . $video_id, $ip_votantes);
    
    // Registrar el voto en logs
    honeywhale_log_voto($video_id, $user_ip);
    
    wp_send_json_success(['votos' => $nuevos_votos]);
}

add_action('wp_ajax_honeywhale_votar', 'honeywhale_votar_video');
add_action('wp_ajax_nopriv_honeywhale_votar', 'honeywhale_votar_video');

/**
 * Función para obtener IP del cliente de forma segura
 */
function honeywhale_get_client_ip() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Función para registrar votos en log
 */
function honeywhale_log_voto($video_id, $ip) {
    $log_file = WP_CONTENT_DIR . '/uploads/hw-votos-log.txt';
    $date = date('Y-m-d H:i:s');
    $log_entry = "{$date} | Video ID: {$video_id} | IP: {$ip}\n";
    
    // Crear directorio si no existe
    if (!file_exists(WP_CONTENT_DIR . '/uploads')) {
        mkdir(WP_CONTENT_DIR . '/uploads', 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Función para generar el formulario de videos dinámicamente
 */
function honeywhale_update_videos_form() {
    // Verificar nonce y permisos
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hw_update_videos_form') || !current_user_can('manage_options')) {
        wp_die('Verificación de seguridad fallida');
    }
    
    $num_videos = isset($_POST['num_videos']) ? intval($_POST['num_videos']) : 6;
    if ($num_videos < 1) $num_videos = 1;
    if ($num_videos > 20) $num_videos = 20;
    
    // Guardar el nuevo número de videos
    update_option('hw_max_videos', $num_videos);
    
    // Obtener información de videos
    $videos = array();
    for ($i = 1; $i <= $num_videos; $i++) {
        $videos[$i] = array(
            'titulo' => get_option('hw_video_titulo_'.$i, $i == 1 ? 'Promo Walmart' : ($i == 2 ? 'Scooter M1 Honey Whale' : ($i == 3 ? 'Review Scooter H4' : 'TÍTULO REAL DEL VIDEO '.$i))),
            'autor' => get_option('hw_video_autor_'.$i, $i == 1 ? 'Ana Metralla' : ($i == 2 ? 'Niña rara' : ($i == 3 ? 'El Pollo' : 'NOMBRE DEL AUTOR '.$i))),
            'url' => get_option('hw_video_url_'.$i, '')
        );
    }
    
    // Generar el HTML
    ob_start();
    for ($i = 1; $i <= $num_videos; $i++): 
    ?>
        <div class="hw-admin-video-item" style="border-bottom: 1px solid #ddd; padding-bottom: 15px; margin-bottom: 15px;">
            <h4>Video <?php echo $i; ?> 
                <a href="#" class="hw-collapse-toggle" data-target="video-<?php echo $i; ?>" style="font-size: 12px; text-decoration: none; margin-left: 10px;">[Contraer]</a>
            </h4>
            <div class="hw-video-config" id="video-<?php echo $i; ?>">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">URL del Video</th>
                        <td>
                            <input type="url" name="hw_video_url_<?php echo $i; ?>" value="<?php echo esc_attr(get_option('hw_video_url_'.$i, '')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Título</th>
                        <td>
                            <input type="text" name="hw_video_titulo_<?php echo $i; ?>" value="<?php echo esc_attr($videos[$i]['titulo']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Autor</th>
                        <td>
                            <input type="text" name="hw_video_autor_<?php echo $i; ?>" value="<?php echo esc_attr($videos[$i]['autor']); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    <?php 
    endfor;
    $output = ob_get_clean();
    echo $output;
    
    wp_die();
}
add_action('wp_ajax_honeywhale_update_videos_form', 'honeywhale_update_videos_form');

/**
 * Función para reiniciar votos (solo admin)
 */
function honeywhale_reset_votes() {
    // Verificar nonce por seguridad
    if (!isset($_POST['hw_nonce']) || !wp_verify_nonce($_POST['hw_nonce'], 'hw_reset_votes')) {
        wp_die('Verificación de seguridad fallida');
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para realizar esta acción');
    }
    
    global $wpdb;
    
    // Borrar todas las opciones de votos
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hw_votos_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hw_ip_votantes_%'");
    
    // Redirigir de vuelta a la página de administración
    wp_redirect(admin_url('admin.php?page=honeywhale-concurso&reset=success'));
    exit;
}

add_action('admin_post_honeywhale_reset_votes', 'honeywhale_reset_votes');

/**
 * Añadir página de administración
 */
function honeywhale_admin_menu() {
    add_menu_page(
        'HoneyWhale Concurso',
        'HW Concurso',
        'manage_options',
        'honeywhale-concurso',
        'honeywhale_admin_page',
        'dashicons-video-alt3',
        30
    );
}

add_action('admin_menu', 'honeywhale_admin_menu');

/**
 * Renderizar página de administración
 */
function honeywhale_admin_page() {
    // Verificar permiso
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Procesar cambios en la configuración
    if (isset($_POST['hw_save_settings']) && isset($_POST['hw_nonce']) && wp_verify_nonce($_POST['hw_nonce'], 'hw_save_settings')) {
        if (isset($_POST['hw_concurso_fecha_fin'])) {
            update_option('hw_concurso_fecha_fin', sanitize_text_field($_POST['hw_concurso_fecha_fin']));
        }
        
        if (isset($_POST['hw_concurso_titulo'])) {
            update_option('hw_concurso_titulo', sanitize_text_field($_POST['hw_concurso_titulo']));
        }
        
        if (isset($_POST['hw_concurso_descripcion'])) {
            update_option('hw_concurso_descripcion', sanitize_text_field($_POST['hw_concurso_descripcion']));
        }
        
        // Número máximo de videos
        $max_videos = isset($_POST['hw_max_videos']) ? intval($_POST['hw_max_videos']) : 6;
        update_option('hw_max_videos', $max_videos);
        
        // Guardar información de videos
        for ($i = 1; $i <= $max_videos; $i++) {
            if (isset($_POST['hw_video_url_'.$i])) {
                update_option('hw_video_url_'.$i, esc_url_raw($_POST['hw_video_url_'.$i]));
            }
            if (isset($_POST['hw_video_titulo_'.$i])) {
                update_option('hw_video_titulo_'.$i, sanitize_text_field($_POST['hw_video_titulo_'.$i]));
            }
            if (isset($_POST['hw_video_autor_'.$i])) {
                update_option('hw_video_autor_'.$i, sanitize_text_field($_POST['hw_video_autor_'.$i]));
            }
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
    }
    
    // Mensaje de confirmación tras reset
    if (isset($_GET['reset']) && $_GET['reset'] == 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>Todos los votos han sido reiniciados correctamente.</p></div>';
    }
    
    // Obtener estadísticas de votos
    $videos = array();
    $max_videos = get_option('hw_max_videos', 6);
    for ($i = 1; $i <= $max_videos; $i++) {
        $videos[$i] = array(
            'titulo' => get_option('hw_video_titulo_'.$i, $i == 1 ? 'Promo Walmart' : ($i == 2 ? 'Scooter M1 Honey Whale' : ($i == 3 ? 'Review Scooter H4' : 'TÍTULO REAL DEL VIDEO '.$i))),
            'autor' => get_option('hw_video_autor_'.$i, $i == 1 ? 'Ana Metralla' : ($i == 2 ? 'Niña rara' : ($i == 3 ? 'El Pollo' : 'NOMBRE DEL AUTOR '.$i))),
            'url' => get_option('hw_video_url_'.$i, '')
        );
    }
    
    $votos = array();
    $total_votos = 0;
    
    foreach ($videos as $id => $video) {
        $votos[$id] = (int) get_option("hw_votos_" . $id, 0);
        $total_votos += $votos[$id];
    }
    
    $fecha_fin = get_option('hw_concurso_fecha_fin', '2025-05-31');
    ?>
    
    <div class="wrap">
        <h1><span class="dashicons dashicons-video-alt3" style="font-size: 30px; height: 30px; width: 30px;"></span> HoneyWhale - Administración del Concurso</h1>
        
        <div class="hw-admin-container" style="display: flex; flex-wrap: wrap; margin-top: 20px;">
            <!-- Estadísticas -->
            <div class="hw-admin-card" style="flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-right: 20px; margin-bottom: 20px;">
                <h2>Estadísticas de Votos</h2>
                <p><strong>Total de votos:</strong> <?php echo $total_votos; ?></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Votos</th>
                            <th>Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $id => $video): 
                            $porcentaje = $total_votos > 0 ? round(($votos[$id] / $total_votos) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?php echo esc_html($video['titulo']); ?></td>
                                <td><?php echo esc_html($video['autor']); ?></td>
                                <td><?php echo $votos[$id]; ?></td>
                                <td>
                                    <div style="background: #f1f1f1; height: 20px; width: 100%; border-radius: 3px; overflow: hidden;">
                                        <div style="background: #ffd219; height: 100%; width: <?php echo $porcentaje; ?>%; text-align: right; padding-right: 5px; color: <?php echo $porcentaje > 40 ? '#000' : 'transparent'; ?>; font-weight: bold; font-size: 12px; line-height: 20px;">
                                            <?php echo $porcentaje > 5 ? $porcentaje . '%' : ''; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Formulario para reiniciar votos -->
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="honeywhale_reset_votes">
                    <?php wp_nonce_field('hw_reset_votes', 'hw_nonce'); ?>
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-secondary" value="Reiniciar Votos" onclick="return confirm('¿Estás seguro? Esta acción no se puede deshacer.');">
                    </p>
                </form>
            </div>
            
            <!-- Configuración -->
            <div class="hw-admin-card" style="flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h2>Configuración del Concurso</h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('hw_save_settings', 'hw_nonce'); ?>
                    
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Título del Concurso</th>
                            <td>
                                <input type="text" name="hw_concurso_titulo" value="<?php echo esc_attr(get_option('hw_concurso_titulo', 'Concurso de Videos HoneyWhale')); ?>" class="regular-text">
                                <p class="description">Título principal que aparecerá en la página del concurso</p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row">Descripción del Concurso</th>
                            <td>
                                <textarea name="hw_concurso_descripcion" class="large-text" rows="3"><?php echo esc_textarea(get_option('hw_concurso_descripcion', 'Vota por tus videos favoritos. La votación termina en:')); ?></textarea>
                                <p class="description">Texto descriptivo que aparecerá debajo del título</p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row">Fecha de finalización</th>
                            <td>
                                <input type="date" name="hw_concurso_fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>">
                                <p class="description">Fecha en que finalizará el concurso (formato YYYY-MM-DD)</p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row">Número de videos</th>
                            <td>
                                <select name="hw_max_videos" id="hw_max_videos">
                                    <?php 
                                    $max_videos = get_option('hw_max_videos', 6);
                                    for ($i = 1; $i <= 20; $i++) {
                                        echo '<option value="' . $i . '"' . selected($max_videos, $i, false) . '>' . $i . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description">Número de videos que se mostrarán en el concurso (máximo 20)</p>
                                <input type="button" class="button" value="Actualizar número de videos" onclick="actualizarVideos()">
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Configuración de Videos</h3>
                    
                    <div class="hw-admin-videos" id="hw-admin-videos" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php 
                        $max_videos = get_option('hw_max_videos', 6);
                        for ($i = 1; $i <= $max_videos; $i++): 
                        ?>
                            <div class="hw-admin-video-item" style="border-bottom: 1px solid #ddd; padding-bottom: 15px; margin-bottom: 15px;">
                                <h4>Video <?php echo $i; ?> 
                                    <a href="#" class="hw-collapse-toggle" data-target="video-<?php echo $i; ?>" style="font-size: 12px; text-decoration: none; margin-left: 10px;">[Contraer]</a>
                                </h4>
                                <div class="hw-video-config" id="video-<?php echo $i; ?>">
                                    <table class="form-table">
                                        <tr valign="top">
                                            <th scope="row">URL del Video</th>
                                            <td>
                                                <input type="url" name="hw_video_url_<?php echo $i; ?>" value="<?php echo esc_attr(get_option('hw_video_url_'.$i, '')); ?>" class="regular-text">
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">Título</th>
                                            <td>
                                                <input type="text" name="hw_video_titulo_<?php echo $i; ?>" value="<?php echo esc_attr($videos[$i]['titulo']); ?>" class="regular-text">
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">Autor</th>
                                            <td>
                                                <input type="text" name="hw_video_autor_<?php echo $i; ?>" value="<?php echo esc_attr($videos[$i]['autor']); ?>" class="regular-text">
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="hw_save_settings" id="submit" class="button button-primary" value="Guardar Cambios">
                    </p>
                </form>
                
                <script>
                    jQuery(document).ready(function($) {
                        // Inicializar toggles existentes
                        initializeToggles();
                        
                        // Función para actualizar el número de videos
                        window.actualizarVideos = function() {
                            const numVideos = $('#hw_max_videos').val();
                            const container = $('#hw-admin-videos');
                            
                            container.html('<p>Cargando...</p>');
                            
                            // Obtener el HTML actualizado mediante AJAX
                            $.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                data: {
                                    action: 'honeywhale_update_videos_form',
                                    num_videos: numVideos,
                                    nonce: '<?php echo wp_create_nonce('hw_update_videos_form'); ?>'
                                },
                                success: function(response) {
                                    container.html(response);
                                    // Reiniciar los toggles
                                    initializeToggles();
                                },
                                error: function(xhr, status, error) {
                                    container.html('<p>Error al actualizar los videos: ' + error + '</p>');
                                    console.error(xhr.responseText);
                                }
                            });
                        };
                        
                        // Función para inicializar los toggles
                        function initializeToggles() {
                            $('.hw-collapse-toggle').off('click').on('click', function(e) {
                                e.preventDefault();
                                const targetId = $(this).data('target');
                                const targetElement = $('#' + targetId);
                                
                                if (targetElement.is(':visible')) {
                                    targetElement.hide();
                                    $(this).text('[Expandir]');
                                } else {
                                    targetElement.show();
                                    $(this).text('[Contraer]');
                                }
                            });
                        }
                    });
                </script>
            </div>
        </div>
        
        <!-- Shortcode Info -->
        <div class="hw-admin-card" style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h2>Cómo usar el Concurso</h2>
            <p>Usa el shortcode <code>[honeywhale_concurso]</code> en cualquier página o entrada para mostrar la galería de videos con votación.</p>
        </div>
    </div>
    <?php
}

/**
 * Función para exportar los resultados
 */
function honeywhale_export_results() {
    // Verificar nonce y permisos
    if (!isset($_GET['hw_export_nonce']) || !wp_verify_nonce($_GET['hw_export_nonce'], 'hw_export_results') || !current_user_can('manage_options')) {
        wp_die('Acceso no autorizado');
    }
    
    // Obtener datos de los videos desde las opciones
    $videos = array();
    $max_videos = get_option('hw_max_videos', 6);
    for ($i = 1; $i <= $max_videos; $i++) {
        $videos[$i] = array(
            'titulo' => get_option('hw_video_titulo_'.$i, $i == 1 ? 'Promo Walmart' : ($i == 2 ? 'Scooter M1 Honey Whale' : ($i == 3 ? 'Review Scooter H4' : 'TÍTULO REAL DEL VIDEO '.$i))),
            'autor' => get_option('hw_video_autor_'.$i, $i == 1 ? 'Ana Metralla' : ($i == 2 ? 'Niña rara' : ($i == 3 ? 'El Pollo' : 'NOMBRE DEL AUTOR '.$i))),
            'url' => get_option('hw_video_url_'.$i, '')
        );
    }
    
    $data = array();
    $data[] = array('ID', 'Título', 'Autor', 'Votos');
    
    foreach ($videos as $id => $video) {
        $votos = (int) get_option("hw_votos_" . $id, 0);
        $data[] = array($id, $video['titulo'], $video['autor'], $votos);
    }
    
    // Generar CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="honeywhale_resultados_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

add_action('admin_post_honeywhale_export_results', 'honeywhale_export_results');

/**
 * Añadir enlace de exportación en la página de administración
 */
function honeywhale_admin_footer() {
    $screen = get_current_screen();
    
    if ($screen->id === 'toplevel_page_honeywhale-concurso') {
        $export_url = admin_url('admin-post.php?action=honeywhale_export_results&hw_export_nonce=' . wp_create_nonce('hw_export_results'));
        ?>
        <script>
            jQuery(document).ready(function($) {
                $('.wrap h1').after('<a href="<?php echo esc_url($export_url); ?>" class="page-title-action">Exportar Resultados</a>');
            });
        </script>
        <?php
    }
}

add_action('admin_footer', 'honeywhale_admin_footer');
                