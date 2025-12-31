# Plugin YGLU e-commerce para WordPress

Este plugin permite conectar un sitio web con WordPress + WooCommerce a YGLU para sincronizar pedidos, generar facturas, albaranes y más.

## Iniciar WordPress localmente con Docker
```sh
make wp-start
```


Este comando inicia un contenedor local de WordPress junto con la base de datos.
Utiliza la última versión de WordPress e instala la CLI de WordPress para configurar automáticamente un usuario administrador con las credenciales definidas en el archivo `.env`.
Una vez completada la instalación, verás en los logs la información necesaria para acceder a WordPress.

> [!NOTE]
> Debes ejecutar el comando en una shell bash, como por ejemplo WSL o Git bash. Si no tienes la utilidad `make` instalada, puedes instalarla con `sudo apt update && sudo apt install make`.
>
> Si falla con un error similar a `Error: This does not seem to be a WordPress installation`, vuelve a ejecutar el comando.

![Iniciar WordPress](doc/wp-start.png)

Después de iniciar el entorno Docker, puedes acceder a WordPress localmente en:

* **Frontend:** http://localhost:8080
* **Backend:** http://localhost:8080/wp-admin
* **PHPMyAdmin:** http://localhost:8180

Tu plugin ya estará instalado automáticamente y listo para ser activado.

![Ejemplo de plugin](doc/plugin-example.png)

## Detener WordPress localmente con Docker
```sh
make wp-stop
```

## Compilar el plugin
```sh
make
```

Este comando ejecuta el script `bin/build.sh` usando el nombre del plugin configurado en `.env`, y genera un archivo `.zip` instalable del plugin de WordPress.

![Compilar plugin](doc/build.png)

Además, el flujo de trabajo de GitHub definido en `.github/workflows/build.yml` compilará automáticamente el plugin y creará una nueva *release* con el archivo `.zip` cada vez que se haga un *push* a la rama `main`.

![Publicar plugin](doc/release.png)

## Desarrollo del plugin

El código fuente del plugin se encuentra en el directorio `src/`.

* `src/index.php`: Configuración general y variables / funciones globales
* `src/admin.php`: Página de administración
* `src/script.js`: Código JavaScript personalizado cargado con el plugin
* `src/style.css`: Código CSS personalizado cargado con el plugin

> [!NOTE]
> `xdebug` está disponible, solo necesitas instalar la extensión [PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) para VSCode e iniciar una sesión de debugging con F5.

**Página de administración:**

![Ejemplo de panel de administración](doc/plugin.png)
