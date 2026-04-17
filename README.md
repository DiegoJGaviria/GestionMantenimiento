# Sistema de Gestión de Mantenimiento de Computadores

## Descripción
Sistema web para la gestión de reparaciones de computadores, desarrollado con PHP, MySQL y Bootstrap.

## Características
- Gestión de usuarios con roles (Administrador/Empleado)
- Control de marcas, clientes, arreglos y diagnósticos
- Dashboard con estadísticas
- Interfaz responsive con Bootstrap
- Seguridad implementada según estándares ISO 27001

## Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache/Nginx con mod_rewrite
- Extensiones PHP: mysqli, session, json

## Instalación

### 1. Clonación del repositorio
```bash
git clone <repository-url>
cd gestion-mantenimiento
```

### 2. Configuración de la base de datos
```sql
-- Ejecutar el script SQL incluido
source Base\ Datos/sistema_arreglo_computadores.sql
```

### 3. Configuración del entorno
- Copiar `config.php` y ajustar las credenciales de base de datos
- Configurar permisos de escritura para `logs/`
- Ajustar configuración de PHP según `.htaccess`

### 4. Configuración web server
- Asegurar que `mod_rewrite` esté habilitado
- Configurar DocumentRoot al directorio `Codigo/`
- Configurar HTTPS en producción

## Estructura del Proyecto
```
Codigo/
├── config.php          # Configuración de aplicación
├── conexion.php        # Conexión a base de datos
├── logger.php          # Sistema de logging de seguridad
├── auth.php           # Autenticación de usuarios
├── login.php          # Página de login
├── index.php          # Dashboard principal
├── usuarios.php       # Gestión de usuarios
├── marca.php          # Gestión de marcas
├── cliente.php        # Gestión de clientes
├── arreglo.php        # Gestión de arreglos
├── diagnostico.php    # Gestión de diagnósticos
├── navbar.php         # Barra de navegación
├── logout.php         # Cierre de sesión
├── estils.css         # Estilos CSS
└── .htaccess          # Configuración de seguridad
```

## Seguridad Implementada

### Autenticación
- Hashing de contraseñas con `password_hash()`
- Verificación con `password_verify()`
- Regeneración de ID de sesión post-login
- Protección CSRF en todos los formularios

### Autorización
- Control de acceso basado en roles
- Validación de permisos en cada página
- Sanitización de entrada de usuario

### Protección de Datos
- Prepared statements para prevenir SQL injection
- Validación de entrada en servidor
- Escape de output con `htmlspecialchars()`
- Headers de seguridad HTTP

### Logging y Monitoreo
- Logging de eventos de seguridad
- Registro de accesos y modificaciones
- Alertas de intentos de intrusión

## Cumplimiento Normativo

### ISO 27001 - Gestión de Seguridad de la Información
- **A.9 Control de acceso**: Implementado control de acceso lógico
- **A.10 Criptografía**: Hashing de contraseñas y protección de datos
- **A.12 Operaciones de seguridad**: Logging y monitoreo
- **A.14 Gestión de incidentes**: Sistema de logging para detección

### ISO 9001 - Gestión de Calidad
- **Planificación**: Arquitectura modular y documentación
- **Control de procesos**: Validación y manejo de errores
- **Mejora continua**: Estructura preparada para auditorías

## Uso del Sistema

### Acceso
1. Acceder a `login.php`
2. Iniciar sesión con credenciales válidas

### Roles de Usuario
- **Administrador**: Acceso completo a todas las funciones
- **Empleado**: Acceso limitado según permisos

### Funciones Principales
- **Dashboard**: Vista general con estadísticas
- **Usuarios**: Gestión de cuentas de usuario
- **Marcas**: Catálogo de marcas de equipos
- **Clientes**: Base de datos de clientes
- **Arreglos**: Gestión de órdenes de reparación
- **Diagnósticos**: Registro de diagnósticos técnicos

## Mantenimiento

### Actualizaciones de Seguridad
- Mantener PHP y dependencias actualizadas
- Revisar logs regularmente
- Realizar backups periódicos

### Monitoreo
- Monitorear logs de seguridad
- Revisar intentos de acceso fallidos
- Auditar cambios en datos sensibles

## Soporte
Para soporte técnico, contactar al equipo de desarrollo.

## Licencia
Este proyecto está bajo licencia [especificar licencia].

## Contribución
1. Fork el proyecto
2. Crear rama para feature (`git checkout -b feature/nueva-funcion`)
3. Commit cambios (`git commit -am 'Agrega nueva función'`)
4. Push a la rama (`git push origin feature/nueva-funcion`)
5. Crear Pull Request