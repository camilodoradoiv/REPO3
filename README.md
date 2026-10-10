# Gestión de envíos (PHP + MySQL)

Aplicación web sencilla para registrar y administrar envíos. Cada envío contiene destinatario, dirección y descripción.

## Funciones
- Crear envíos.
- Listar envíos con fecha de registro.
- Buscar por destinatario, dirección o descripción.
- Editar y eliminar registros.
- Crear automáticamente la base de datos y la tabla si el usuario MySQL tiene permisos.

## Requisitos
- PHP 8.1 o superior recomendado.
- Extensiones `pdo_mysql` y `mbstring`.
- MySQL/MariaDB.
- Servidor web como Apache o Nginx.

## Instalación
1. Descomprime la carpeta en el directorio público de tu hosting (por ejemplo `public_html/envios/`).
2. Revisa `config.php`. Puedes dejar la configuración incluida o establecer las variables de entorno `DB_HOST`, `DB_USER`, `DB_PASSWORD` y `DB_NAME`.
3. Abre la URL donde instalaste la aplicación.
4. La primera carga intenta crear la base de datos `ccdoriv_enviosgit` y la tabla `envios`.

## Importante sobre permisos
El usuario MySQL debe tener permisos para crear la base de datos. En algunos hostings compartidos, la base de datos debe crearse primero desde el panel de control y el usuario debe tener acceso a ella. Si el hosting no permite `CREATE DATABASE`, crea la base de datos desde el panel y vuelve a cargar la aplicación.

## Seguridad
- Cambia la contraseña de base de datos si esta credencial se compartió en un lugar público.
- En producción, es preferible guardar las credenciales en variables de entorno fuera del directorio público.
- Configura HTTPS y mantén PHP actualizado.
- No publiques `config.php` ni compartas el ZIP públicamente.
