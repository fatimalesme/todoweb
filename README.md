# ToDoWeb

Aplicacion web de gestion de tareas personales con gamificacion, temporizador Pomodoro y graficas de progreso. Desarrollada con PHP, MySQL, HTML, CSS y JavaScript vanilla como proyecto final del ciclo DAW.

## Funcionalidades

- Registro e inicio de sesion de usuarios con contrasenas cifradas
- Modo invitado sin registro (datos en sesion, sin base de datos)
- Crear, editar, completar, eliminar y postergar tareas
- Organizar tareas en listas y categorias personalizadas
- Fecha y hora limite por tarea con avisos de proximidad en el sidebar
- Sistema de gamificacion: XP, niveles espaciales (Nebulosa, Destello, Fulgor, Supernova), logros y racha diaria
- Temporizador Pomodoro (25 min trabajo / 5 min descanso) vinculado a tareas concretas
- Calendario visual en el sidebar con marcadores de dias con tareas
- Pagina de progreso con grafica de dona, grafica de barras semanal y frase motivadora dinamica
- Diseno responsive (movil, tablet y escritorio)

## Tecnologias

- PHP 7.4+
- MySQL 5.7+
- HTML5 / CSS3
- JavaScript vanilla (sin frameworks)
- Chart.js (graficas de progreso)
- Google Fonts (tipografia Inter y DM Serif Display)

## Instalacion

### Requisitos

- Servidor web con PHP 7.4 o superior (XAMPP recomendado)
- MySQL 5.7 o superior
- Extensiones PHP necesarias: `mysqli`, `session`
- Conexion a internet solo para cargar Google Fonts y Chart.js desde CDN

### Pasos

1. Clona o descarga el repositorio en la carpeta de tu servidor web:

```
git clone https://github.com/fatimalesme/todoweb.git
```

En XAMPP la ruta habitual es `C:\xampp\htdocs\ToDoWeb`

2. Crea la base de datos importando el script SQL. Abre phpMyAdmin, crea una base de datos llamada `todoweb_db` y ejecuta el contenido de `sql/estructura.sql`. O desde terminal:

```bash
mysql -u root -p < sql/estructura.sql
```

3. Comprueba la conexion en `includes/db.php` y ajusta las credenciales si es necesario:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'todoweb_db');
```

4. Abre el navegador y accede a:

```
http://localhost/ToDoWeb/login.php
```

5. Registrate como nuevo usuario desde la pantalla de login o accede como invitado para probar sin guardar datos.

## Estructura del proyecto

```
ToDoWeb/
├── assets/
│   ├── css/
│   │   ├── style.css          (estilos generales y tareas)
│   │   ├── progreso.css       (estilos pagina de progreso)
│   │   └── pomodoro.css       (estilos pagina pomodoro)
│   └── js/
│       ├── app.js             (logica principal y AJAX)
│       ├── validaciones.js    (validacion de formularios)
│       ├── notifications.js   (animacion de avisos)
│       └── pomodoro.js        (timer Pomodoro con anillo SVG)
├── controllers/
│   ├── authController.php     (login, registro, logout)
│   ├── tareasController.php   (CRUD de tareas y listas)
│   ├── pomodoroController.php (guardar segundos trabajados)
│   └── progresoController.php (redirige a progreso.php)
├── includes/
│   ├── db.php                 (conexion a la base de datos)
│   ├── session.php            (control de acceso)
│   ├── functions.php          (funciones: gamificacion, CSRF, formato)
│   └── notifications.php      (avisos de tareas proximas a vencer)
├── sql/
│   └── estructura.sql         (script de creacion de la base de datos)
├── index.php                  (pagina principal con lista de tareas)
├── login.php                  (pantalla de acceso)
├── registro.php               (crear cuenta nueva)
├── pomodoro.php               (temporizador Pomodoro)
├── progreso.php               (estadisticas y graficas)
├── logout.php                 (cerrar sesion)
└── README.md
```

## Seguridad implementada

- Contrasenas cifradas con `password_hash()` usando bcrypt y verificadas con `password_verify()`
- Proteccion CSRF en todos los formularios y peticiones fetch() mediante tokens de sesion
- Consultas preparadas con `prepared statements` y `bind_param()` en toda la capa de datos
- Escape de salida con `htmlspecialchars()` para prevenir XSS
- Limite de intentos de login para dificultar ataques de fuerza bruta
- Regeneracion de ID de sesion al hacer login para evitar session fixation
- Destruccion completa de la sesion y cookie al hacer logout

## Despliegue en produccion

- Cambiar las credenciales de la base de datos en `includes/db.php`
- Activar HTTPS en el servidor
- Configurar `error_reporting(0)` en PHP para no mostrar errores al usuario
- Asegurarse de que las extensiones `mysqli` y `session` estan activas en `php.ini`