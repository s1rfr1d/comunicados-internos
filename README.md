# Comunicados Internos para WordPress

## Descripción
Este plugin para WordPress está desarrollado para gestionar, auditar y registrar el acuse de recibo de comunicados corporativos internos. Su propósito principal es asegurar que los encargados de tienda (o cualquier grupo de usuarios designado) lean la información publicada, proporcionando a los departamentos de Recursos Humanos o Marketing una herramienta de seguimiento y estadística en tiempo real.

## Características Principales
* **Panel de Estadísticas (Dashboard):** Interfaz administrativa que muestra el total de comunicados publicados, el porcentaje de lectura global, los usuarios con respuestas más rápidas y una lista de empleados pendientes de lectura.
* **Confirmación Asíncrona (AJAX):** Botón de "He leído y comprendido" que registra la fecha y hora exacta de lectura sin necesidad de recargar la página.
* **Control de Evaluados:** Permite al administrador seleccionar específicamente qué usuarios están obligados a confirmar la lectura. Los usuarios no seleccionados no verán los elementos de seguimiento.
* **Auditoría Pública por Publicación:** Genera una tabla al final de cada comunicado detallando el estado de lectura (Leído / No leído) de cada usuario evaluado.
* **Restricción de Acceso:** Crea automáticamente un rol de usuario denominado "Encargado de Tienda" (tiendas). El plugin restringe la navegación de estos usuarios, forzando la redirección hacia la sección de comunicados si intentan acceder a otras áreas del sitio.

## Requisitos del Sistema
* WordPress 5.0 o superior.
* PHP 7.4 o superior.
* Tema compatible con la ejecución de filtros estándar de contenido (the_content).

## Instrucciones de Instalación

1. Descargue el código de este repositorio en formato `.zip` utilizando el botón "Code" > "Download ZIP" de GitHub.
2. Inicie sesión en el panel de administración de su instalación de WordPress.
3. Diríjase a la sección **Plugins** en el menú lateral y seleccione **Añadir nuevo plugin**.
4. Haga clic en el botón **Subir plugin** situado en la parte superior de la pantalla.
5. Seleccione el archivo `.zip` descargado previamente y presione **Instalar ahora**.
6. Una vez finalizada la instalación, haga clic en **Activar plugin**.

## Configuración Inicial

Para que el sistema comience a registrar los acuses de recibo de forma correcta, es necesario realizar una configuración inicial:

1. Tras la activación, diríjase al nuevo menú lateral llamado **Comunicados**.
2. En la pantalla del panel de control, localice la sección titulada **Encargados a Evaluar**.
3. Marque las casillas correspondientes a los usuarios (encargados de tienda) que deberán confirmar obligatoriamente la lectura de las publicaciones.
4. Haga clic en el botón **Guardar Selección**. A partir de este momento, el botón de lectura y la tabla de seguimiento aparecerán en los comunicados exclusivamente para estos usuarios.

## Nota sobre Notificaciones Push
Para alertar a los usuarios sobre la publicación de nuevos comunicados mediante ventanas emergentes en el navegador web, incluso cuando la pestaña de WordPress se encuentra cerrada, se recomienda integrar este plugin de forma complementaria con el servicio gratuito **OneSignal Web Push Notifications**.
