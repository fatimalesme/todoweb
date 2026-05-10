# ToDoWeb

Aplicacion web de gestion de tareas con listas, notificaciones, temporizador Pomodoro y graficas de progreso. Desarrollada con PHP, MySQL, HTML, CSS y JavaScript vanilla.

## Funcionalidades

- Registro e inicio de sesion de usuarios
- Modo invitado (sin base de datos, datos en sesion)
- Crear, editar, completar, eliminar y postergar tareas
- Organizar tareas en listas personalizadas
- Avisos de tareas proximas a vencer o vencidas
- Temporizador Pomodoro (25 min trabajo / 5 min descanso)
- Pagina de progreso con grafica de tareas completadas vs pendientes
- Diseno responsive (movil y escritorio)

## Tecnologias

- PHP 8.x
- MySQL 8.x
- HTML5 / CSS3
- JavaScript (sin frameworks)
- Chart.js (graficas)

## Instalacion

### Requisitos

- Servidor web con PHP 8.0 o superior (XAMPP, WAMP, Laragon, etc.)
- MySQL 8.0 o superior
- Extensiones PHP necesarias: `mysqli`, `mbstring`

### Pasos

1. Clona o descarga el repositorio en la carpeta de tu servidor web (por ejemplo `htdocs` en XAMPP):

```
git clone https://github.com/tu-usuario/todoweb.git
```

2. Importa la base de datos:

```bash
mysql -u root -p < sql/estructura.sql
```

O abre phpMyAdmin, crea la base de datos `todoweb_db` y ejecuta el contenido de `sql/estructura.sql`.

3. Configura la conexion en `includes/db.php` si tus credenciales MySQL son diferentes:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'tu_contrasena');
define('DB_NAME', 'todoweb_db');
```

4. Abre el navegador y accede a:

```
http://localhost/todoweb/login.php
```

5. Usuario de prueba incluido en el SQL:
   - Usuario: `admin`
   - Contrasena: `admin123`

> Cambia o elimina este usuario antes de subir a produccion.

## Estructura del proyecto

```
Proyecto/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── app.js           (logica principal y AJAX)
│       ├── validaciones.js  (validacion de formularios)
│       ├── notifications.js (animacion de avisos)
│       ├── pomodoro.js      (timer Pomodoro)
│       └── graficas.js      (grafica de progreso con Chart.js)
├── controllers/
│   ├── authController.php
│   ├── tareasController.php
│   ├── pomodoroController.php
│   └── progresoController.php
├── includes/
│   ├── db.php
│   ├── session.php
│   ├── functions.php
│   └── notifications.php
├── sql/
│   └── estructura.sql
├── index.php
├── login.php
├── registro.php
├── pomodoro.php
├── progreso.php
└── README.md
```

## Seguridad implementada

- Contrasenas encriptadas con `password_hash()` y verificadas con `password_verify()`
- Proteccion CSRF en todos los formularios mediante tokens
- Consultas preparadas con `prepared statements` en toda la capa de datos
- Escape de salida con `htmlspecialchars()` para prevenir XSS
- Limite de intentos de login para dificultar ataques de fuerza bruta
- Session regeneration al hacer login para evitar session fixation
- Destruccion completa de la sesion y cookie al hacer logout

## Despliegue en produccion

- Cambiar las credenciales de la base de datos
- Eliminar el usuario `admin` de prueba o cambiar su contrasena
- Activar HTTPS en el servidor
- Configurar `error_reporting(0)` en PHP para no mostrar errores al usuario
