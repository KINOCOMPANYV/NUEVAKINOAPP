# Guía de Despliegue - KINO COMPANY SAS

Esta guía te ayudará a desplegar tu aplicación en **Railway** (recomendado) o **Render**.

## ¿Por qué Railway?

✅ **Railway** es la opción recomendada porque:
- Ofrece MySQL administrado (que necesitas)
- Soporte nativo para Docker
- Configuración simple de variables de entorno
- Plan gratuito generoso para empezar

## Requisitos Previos

1. Cuenta en [Railway.app](https://railway.app)
2. Git instalado localmente
3. Tu código en un repositorio Git (GitHub, GitLab, etc.)

---

## Opción 1: Despliegue en Railway (Recomendado)

### Paso 1: Preparar el Repositorio

Asegúrate de que tu repositorio tenga estos archivos en la raíz (o en `htdocs`):
- ✅ `Dockerfile` (ya creado)
- ✅ `api.php` (modificado para usar variables de entorno)
- ✅ `requirements.txt`
- ✅ Todos los demás archivos de la aplicación

**Commit y push** los cambios:
```bash
git add .
git commit -m "Add Dockerfile and environment variable support"
git push origin main
```

### Paso 2: Crear Proyecto en Railway

1. Ve a [railway.app](https://railway.app) e inicia sesión
2. Click en **"New Project"**
3. Selecciona **"Deploy from GitHub repo"**
4. Autoriza Railway para acceder a tu repositorio
5. Selecciona tu repositorio `KINOAPP`

### Paso 3: Agregar Base de Datos MySQL

1. En tu proyecto de Railway, click en **"+ New"**
2. Selecciona **"Database"** → **"Add MySQL"**
3. Railway creará automáticamente una base de datos MySQL

### Paso 4: Configurar Variables de Entorno

1. Click en tu servicio web (el que tiene el Dockerfile)
2. Ve a la pestaña **"Variables"**
3. Agrega las siguientes variables:

```bash
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_NAME=${{MySQL.MYSQL_DATABASE}}
DB_USER=${{MySQL.MYSQL_USER}}
DB_PASS=${{MySQL.MYSQL_PASSWORD}}
```

> **Nota**: Railway auto-completa estas variables con referencias a tu base de datos MySQL. Solo copia y pega exactamente como se muestra arriba.

### Paso 5: Crear las Tablas de la Base de Datos

Railway no ejecuta automáticamente scripts SQL. Necesitas crear las tablas manualmente:

1. En Railway, click en tu servicio **MySQL**
2. Ve a la pestaña **"Data"** o **"Connect"**
3. Copia las credenciales de conexión
4. Usa un cliente MySQL (como **MySQL Workbench**, **DBeaver**, o **phpMyAdmin**) para conectarte
5. Ejecuta el siguiente SQL:

```sql
CREATE TABLE IF NOT EXISTS documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  date DATE NOT NULL,
  path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  document_id INT NOT NULL,
  code VARCHAR(100) NOT NULL,
  FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  INDEX idx_code (code),
  INDEX idx_document_id (document_id)
);
```

**Alternativa**: Puedes usar el **Railway CLI** para ejecutar el SQL:
```bash
# Instalar Railway CLI
npm i -g @railway/cli

# Login
railway login

# Conectar al proyecto
railway link

# Ejecutar SQL
railway run mysql -u root -p < schema.sql
```

### Paso 6: Configurar el Dominio (Opcional)

1. En tu servicio web, ve a **"Settings"**
2. En la sección **"Networking"**, click en **"Generate Domain"**
3. Railway te dará un dominio público como `tu-app.up.railway.app`

### Paso 7: Verificar el Despliegue

1. Railway comenzará a construir tu aplicación automáticamente
2. Puedes ver los logs en la pestaña **"Deployments"**
3. Una vez completado, visita tu dominio
4. Deberías ver la pantalla de login de tu aplicación

---

## Opción 2: Despliegue en Render

> ⚠️ **Limitación**: Render no ofrece MySQL administrado en el plan gratuito (solo PostgreSQL). Necesitarías:
> - Migrar a PostgreSQL, o
> - Usar un servicio externo de MySQL (como PlanetScale, Railway MySQL standalone, etc.)

### Si decides usar Render con PostgreSQL:

1. Modifica `api.php` para usar PostgreSQL en lugar de MySQL
2. Cambia el DSN a: `pgsql:host=$host;port=5432;dbname=$dbname`
3. Sigue la [documentación de Render](https://render.com/docs/deploy-php)

---

## Solución de Problemas

### Error: "No se pudo conectar a la base de datos"
- Verifica que las variables de entorno estén correctamente configuradas
- Asegúrate de que el servicio MySQL esté corriendo
- Revisa los logs en Railway

### Error: "uploads/ permission denied"
- El Dockerfile ya configura los permisos correctos
- Si persiste, verifica que la carpeta `uploads/` exista en tu repositorio

### La aplicación no carga
- Revisa los logs de construcción en Railway
- Verifica que el `Dockerfile` esté en la raíz del repositorio
- Asegúrate de que todos los archivos estén en el commit

### Archivos PDF no se suben
- Verifica el límite de tamaño de archivo en Railway (generalmente 100MB)
- Revisa los permisos de la carpeta `uploads/`

---

## Migración de Datos Existentes

Si ya tienes datos en tu base de datos actual (`sql200.infinityfree.com`):

### Opción A: Exportar e Importar con mysqldump

```bash
# Exportar desde la BD actual
mysqldump -h sql200.infinityfree.com -u if0_39064130 -p if0_39064130_buscador > backup.sql

# Importar a Railway
mysql -h <RAILWAY_HOST> -u <RAILWAY_USER> -p <RAILWAY_DB> < backup.sql
```

### Opción B: Usar phpMyAdmin

1. Exporta desde tu hosting actual usando phpMyAdmin
2. Importa en Railway usando un cliente MySQL

---

## Costos Estimados

### Railway (Plan Gratuito)
- **$5 USD de crédito mensual** (suficiente para proyectos pequeños)
- Si excedes: ~$0.000463/GB-hora para la base de datos
- ~$0.000231/GB-hora para el servicio web

### Railway (Plan Pro - $20/mes)
- Incluye $20 de crédito
- Sin límite de proyectos
- Mejor rendimiento

---

## Próximos Pasos

1. ✅ Despliega en Railway siguiendo los pasos anteriores
2. ✅ Migra tus datos existentes
3. ✅ Prueba todas las funcionalidades (subir, buscar, eliminar)
4. ✅ Configura un dominio personalizado (opcional)
5. ✅ Configura backups automáticos de la base de datos

---

## Recursos Adicionales

- [Documentación de Railway](https://docs.railway.app)
- [Railway CLI](https://docs.railway.app/develop/cli)
- [Soporte de Railway](https://help.railway.app)

---

**¿Necesitas ayuda?** Revisa los logs en Railway o contacta al soporte técnico.
