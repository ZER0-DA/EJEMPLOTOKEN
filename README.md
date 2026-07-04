# 🔐 API REST con JWT — Laboratorio Seguridad

> **Desarrollo de Software VII** · Universidad Tecnológica  
> Facultad de Ingeniería en Sistemas · Campus Victor Levis Sasso  
> Instructor: Ing. Irina Fong · I Semestre 2026

---

## 📋 Descripción

API REST desarrollada en **PHP puro** con arquitectura **Stateless**, protegida mediante **JSON Web Tokens (JWT)**. Implementa un CRUD completo de productos con autenticación centralizada y contraseñas hasheadas con **BCRYPT**.

---

## 🛠️ Tecnologías utilizadas

| Tecnología | Uso |
|---|---|
| PHP 8.3 | Backend / Lógica del servidor |
| MySQL | Base de datos |
| firebase/php-jwt v7 | Generación y validación de tokens JWT |
| Composer | Gestión de dependencias |
| Postman | Pruebas de endpoints |
| WampServer | Servidor local (Apache + PHP + MySQL) |

---

## 📁 Estructura del proyecto

```
EJEMPLOTOKENS/
├── api/
│   └── products.php      # CRUD de productos (GET, POST, PUT, DELETE)
├── src/
│   └── AuthService.php   # Clase que centraliza la autenticación JWT
├── vendor/               # Dependencias de Composer (no se sube al repo)
├── composer.json         # Configuración de dependencias
├── composer.lock         # Versiones exactas instaladas
├── config.php            # Claves secretas (no se sube al repo)
├── login.php             # Endpoint de autenticación
├── index.php             # Front Controller (valida JWT antes del CRUD)
├── database.sql          # Schema de la base de datos
└── .gitignore
```

---

## ⚙️ Instalación y configuración

### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/EJEMPLOTOKENS.git
cd EJEMPLOTOKENS
```

### 2. Instalar dependencias
```bash
composer install
```

### 3. Crear el archivo `config.php`
Este archivo **no está en el repositorio** por seguridad. Créalo en la raíz del proyecto con este contenido:

```php
<?php
define('JWT_SECRET_KEY', 'tu_clave_secreta_aqui');
define('JWT_ALGORITHM',  'HS256');
define('JWT_EXPIRACION', 3600); // 1 hora
```

### 4. Crear la base de datos
Abre phpMyAdmin y ejecuta el archivo `database.sql`:
```
phpMyAdmin → pestaña SQL → pegar contenido de database.sql → Continuar
```

### 5. Iniciar WampServer
Asegúrate de que Apache y MySQL estén corriendo (icono verde en la bandeja del sistema).

---

## 🔑 Uso de la API

### Autenticación — Obtener token

```
POST http://localhost/EJEMPLOTOKENS/login.php
```

**Body (JSON):**
```json
{
    "username": "admin",
    "password": "admin123"
}
```

**Respuesta:**
```json
{
    "success": true,
    "message": "Login exitoso.",
    "token": "eyJ0eXAiOiJKV1QiLC..."
}
```

> ⚠️ El usuario `admin` se crea automáticamente la primera vez que se llama a `login.php`.

---

### Endpoints protegidos

Todos los endpoints requieren el token en el header:
```
Authorization: Bearer <token>
```

| Método | URL | Descripción |
|---|---|---|
| `GET` | `/index.php` | Listar todos los productos |
| `GET` | `/index.php?id=1` | Obtener producto por ID |
| `POST` | `/index.php` | Crear nuevo producto |
| `PUT` | `/index.php?id=1` | Actualizar producto |
| `DELETE` | `/index.php?id=1` | Eliminar producto |

---

### Ejemplos de uso

**GET — Listar productos**
```
GET http://localhost/EJEMPLOTOKENS/index.php
Authorization: Bearer <token>
```

**POST — Crear producto**
```
POST http://localhost/EJEMPLOTOKENS/index.php
Authorization: Bearer <token>
Content-Type: application/json

{
    "codigo": "A001",
    "producto": "Mouse óptico",
    "precio": 10.50,
    "cantidad": 5
}
```

**PUT — Actualizar producto**
```
PUT http://localhost/EJEMPLOTOKENS/index.php?id=1
Authorization: Bearer <token>
Content-Type: application/json

{
    "codigo": "A001",
    "producto": "Mouse óptico inalámbrico",
    "precio": 15.99,
    "cantidad": 8
}
```

**DELETE — Eliminar producto**
```
DELETE http://localhost/EJEMPLOTOKENS/index.php?id=1
Authorization: Bearer <token>
```

---

## 🔒 Seguridad implementada

| Actividad | Implementación |
|---|---|
| Blindaje de credenciales | `config.php` fuera del repo (`.gitignore`) |
| Clase AuthService | Centraliza generación y validación de JWT |
| Hashing de contraseñas | `password_hash()` con `PASSWORD_BCRYPT` al registrar |
| Verificación de contraseñas | `password_verify()` al hacer login |
| Front Controller | `index.php` valida el token antes de cualquier operación |
| CRUD protegido | Ningún endpoint es accesible sin JWT válido |
| Prevención SQL Injection | PDO con prepared statements en todas las consultas |

---

## 🧪 Pruebas con Postman

### Escenario Negativo (sin token → 401)
```
GET http://localhost/EJEMPLOTOKENS/index.php
```
**Respuesta:**
```json
{
    "success": false,
    "message": "Token no proporcionado.",
    "data": null
}
```

### Escenario Positivo (con token → 200)
Agrega el header `Authorization: Bearer <token>` y realiza cualquier operación CRUD.

---

## 📦 Dependencias

```json
{
    "require": {
        "firebase/php-jwt": "^7.0"
    }
}
```

---

## 📝 Notas

- La carpeta `vendor/` y el archivo `config.php` están en `.gitignore` y no se suben al repositorio.
- Para recrear el entorno, ejecuta `composer install` y crea tu propio `config.php`.
- El usuario `admin` se genera automáticamente con contraseña hasheada la primera vez que se llama a `login.php`.

---

*Laboratorio desarrollado con fines académicos · Desarrollo de Software VII · 2026*