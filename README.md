# 📦 Sistema de Inventario

Un sistema completo de gestión de inventario desarrollado con PHP, MySQL y diseño responsive moderno.

## 🚀 Características

- ✅ **Registro de Usuarios** - Crear cuentas nuevas fácilmente
- ✅ **Inicio de Sesión Seguro** - Autenticación con hash de contraseñas
- ✅ **Dashboard Interactivo** - Estadísticas en tiempo real
- ✅ **Gestión de Productos** - Agregar, editar, eliminar y buscar productos
- ✅ **Sistema de Categorías** - Organización por categorías
- ✅ **Reportes Avanzados** - Análisis de stock, valor total, etc.
- ✅ **Múltiples Usuarios** - Sistema multiusuario con permisos
- ✅ **Interfaz Responsive** - Funciona en desktop y móvil
- ✅ **Búsqueda y Filtros** - Localizar productos rápidamente

## 📋 Requisitos

- **XAMPP** (Apache + MySQL + PHP)
- **PHP 7.4+**
- **MySQL 5.7+**
- **Navegador web moderno**

## �️ Base de Datos

### Archivos de Instalación
- **`inventario_db.sql`** - Archivo completo con toda la estructura de BD
- **`crear_bd.php`** - Script web que lee automáticamente el archivo SQL
- **`instalar_bd.bat`** - Script de Windows para instalación desde terminal

### Tablas Incluidas
- ✅ **`usuarios`** - Gestión de usuarios y permisos
- ✅ **`categorias`** - Organización de productos
- ✅ **`proveedores`** - Información de proveedores
- ✅ **`inventario`** - Productos principales del sistema
- ✅ **`movimientos_inventario`** - Historial de cambios en stock
- ✅ **`ventas`** y **`detalle_ventas`** - Sistema de ventas
- ✅ **Vistas optimizadas** - Para consultas complejas
- ✅ **Triggers automáticos** - Auditoría de movimientos

### Características Avanzadas
- **Auditoría automática** - Triggers registran todos los movimientos
- **Vistas optimizadas** - Consultas complejas predefinidas
- **Índices estratégicos** - Optimización de rendimiento
- **Relaciones completas** - Foreign keys y constraints
- **Datos de ejemplo** - Sistema listo para usar inmediatamente

## 📁 Estructura del Proyecto

```
P PERSON/
├── index.php              # Página de login
├── registro.php           # Página de registro de usuarios
├── dashboard.php          # Dashboard principal
├── inventario.php         # Gestión de productos
├── agregar_producto.php   # Formulario agregar producto
├── editar_producto.php    # Formulario editar producto
├── categorias.php         # Gestión de categorías
├── reportes.php           # Reportes y estadísticas
├── usuarios.php           # Gestión de usuarios (admin)
├── configuracion.php      # Configuración del perfil
├── logout.php             # Cerrar sesión
├── crear_bd.php           # Script web de instalación BD
├── instalar_bd.bat        # Script Windows de instalación BD
├── inventario_db.sql      # Archivo SQL de la base de datos
├── reset_admin.php        # Resetear contraseña admin
├── conexion.php           # Configuración BD
├── layout.php             # Layout principal con menú
├── layout_footer.php      # Footer del layout
└── README.md             # Este archivo
```

## 🛠️ Instalación

### Opción 1: Instalación Web (Recomendada)
1. Inicia XAMPP (Apache + MySQL)
2. Ve a: `http://localhost/P%20PERSON/crear_bd.php`
3. La base de datos se creará automáticamente

### Opción 2: Instalación desde Terminal
1. Inicia XAMPP (Apache + MySQL)
2. Ejecuta el archivo `instalar_bd.bat` (doble clic)
3. O desde terminal: `c:\xampp\mysql\bin\mysql.exe -u root < inventario_db.sql`

### Verificación
- Usuario de prueba: `admin` / `123456`
- Accede a: `http://localhost/P%20PERSON/`

## 🎯 Funcionalidades por Página

### Dashboard
- Estadísticas generales del inventario
- Alertas de stock bajo y productos agotados
- Accesos rápidos a funciones principales
- Vista general del valor total del inventario

### Inventario
- Lista completa de productos con filtros de búsqueda
- Estados de stock (Disponible, Stock Bajo, Agotado)
- Acciones: Editar y Eliminar productos
- Estadísticas rápidas de la vista actual

### Agregar Producto
- Formulario completo para nuevos productos
- Validación de códigos únicos
- Categorías existentes + opción de nueva categoría
- Campos: código, nombre, cantidad, precio, categoría, descripción

### Categorías
- Gestión completa de categorías
- Estadísticas por categoría (productos, unidades, valor)
- Agregar y eliminar categorías
- Validación para evitar eliminar categorías con productos

### Reportes
- Reportes de stock bajo (< 5 unidades)
- Productos agotados
- Valor por categoría
- Estadísticas generales del inventario

### Registro de Usuarios
- Formulario público de registro
- Validación de contraseñas y usuarios únicos
- Confirmación de contraseña
- Redirección automática al login tras registro exitoso

### Usuarios (Solo Admin)
- Gestión de usuarios del sistema
- Agregar nuevos usuarios
- Ver estadísticas por usuario
- Eliminar usuarios (excepto admin)

### Configuración
- Actualizar perfil personal
- Cambiar contraseña
- Información del sistema
- Acciones de mantenimiento

## 🔐 Usuarios por Defecto

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin   | 123456     | Administrador |

## 🛡️ Seguridad

- **Hash de contraseñas** con `password_hash()`
- **Prepared statements** para prevenir SQL injection
- **Validación de sesiones** en todas las páginas
- **Escape de HTML** para prevenir XSS
- **Verificación de permisos** por usuario

## 📱 Responsive Design

El sistema está completamente optimizado para:
- 💻 **Desktop** - Vista completa con sidebar expandido
- 📱 **Tablet** - Sidebar colapsable
- 📱 **Móvil** - Sidebar oculto, navegación touch-friendly

## � Nuevas Funcionalidades

### Sistema de Auditoría
- **Movimientos automáticos** - Cada cambio en stock se registra automáticamente
- **Historial completo** - Seguimiento de entradas, salidas y ajustes
- **Responsabilidad** - Cada movimiento está ligado a un usuario

### Gestión Avanzada
- **Proveedores** - Base de datos de proveedores con contacto
- **Categorías estructuradas** - Sistema jerárquico de categorías
- **Precios duales** - Costo y precio de venta separados
- **Stock mínimo** - Alertas configurables por producto

### Vistas y Reportes
- **Vista productos completa** - Información unificada de productos
- **Estadísticas generales** - Métricas calculadas automáticamente
- **Movimientos recientes** - Historial de actividad reciente

### Cambiar Colores del Tema
Edita las variables CSS en `layout.php`:
```css
--primary-color: #667eea;
--secondary-color: #764ba2;
```

## 🐛 Solución de Problemas

### Error: "Contraseña incorrecta"
1. Ve a: `http://localhost/P%20PERSON/reset_admin.php`
2. Esto restablecerá la contraseña del admin a `123456`

### Error: "No se puede conectar a la BD"
1. Verifica que XAMPP esté ejecutándose
2. Confirma que la BD existe en phpMyAdmin
3. Revisa las credenciales en `conexion.php`

### Error: "Página no encontrada"
1. Asegúrate de que todos los archivos estén en la carpeta correcta
2. Verifica que Apache esté ejecutándose
3. Confirma la URL: `http://localhost/P%20PERSON/`

## 📊 Estadísticas Incluidas

- **Total de productos** por usuario
- **Valor total del inventario**
- **Productos con stock bajo**
- **Productos agotados**
- **Valor por categoría**
- **Estadísticas por usuario**
- **Precios promedio y extremos**

## 🚀 Próximas Funcionalidades

- [ ] Exportar reportes a PDF/Excel
- [ ] Códigos de barras
- [ ] Alertas por email
- [ ] API REST
- [ ] Backup automático
- [ ] Historial de cambios

## 📄 Licencia

Este proyecto es de código abierto y gratuito para uso personal y comercial.

## 🤝 Contribuir

Si encuentras errores o quieres mejorar el sistema:
1. Reporta issues en GitHub
2. Envía pull requests
3. Sugiere nuevas funcionalidades

---

**Desarrollado con ❤️ usando PHP, MySQL y CSS moderno**