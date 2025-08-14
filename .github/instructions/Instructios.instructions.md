---
applyTo: '**'
---
# Perfil Desarrollador Web - WordPress & WooCommerce

## Objetivo
Todo el código generado debe seguir el stack, metodologías y buenas prácticas de desarrollo profesional en WordPress + WooCommerce. No usar tecnologías fuera de esta lista sin que se indique explícitamente.
y todo lo relacionado con el desarrollo en WordPress y WooCommerce. ten presente que es para una tienda de licores por lo que cada ejemplo que se genere debe tener en cuenta esta temática.

---

## Lenguajes principales
- PHP (versión mínima 7.4, preferiblemente 8.0+)
- JavaScript (ES6+ para scripts frontend y admin)
- HTML5
- CSS3

## Frameworks y plataformas
- WordPress (última versión estable)
- WooCommerce (última versión estable)
- Laravel (solo para proyectos externos a WordPress que lo requieran)
- Bootstrap o Tailwind CSS (si se especifica para el tema)

## Librerías y APIs
- WordPress Hooks (acciones y filtros)
- WooCommerce Hooks y Functions (`WC_Order`, `$product`, etc.)
- jQuery (solo si es necesario y compatible)
- REST API de WordPress/WooCommerce
- $wpdb para tablas personalizadas
- WC_Order_Query para consultas optimizadas

## Estructura y prácticas de desarrollo
- Código de plugins y temas siguiendo los estándares de codificación de WordPress (PHPCS)
- Cabecera de plugin con metadatos obligatorios
- Organización modular del código:
  - Includes / Inc
  - Hooks
  - Shortcodes
  - Admin pages
- Uso de prefijos en funciones, clases y tablas para evitar colisiones
- Internacionalización (i18n) y localización (l10n) de todos los textos
- Sanitización y escape de datos en entradas/salidas
- Uso de Nonces para seguridad en formularios

## Base de datos
- Tablas personalizadas usando $wpdb
- Tipos de post personalizados (CPT) cuando corresponda
- Campos personalizados (metaboxes) y taxonomías personalizadas

## Funcionalidades clave que Copilot debe generar correctamente
- Creación de plugins básicos con hooks
- Registro y manejo de campos personalizados en checkout
- Filtros dinámicos de precios y descuentos
- Shortcodes personalizados
- Integración de sistemas de descuentos por proveedor y producto
- Reportes en el admin usando WP_List_Table o similares
- Optimización de consultas para rendimiento

## Estándares de código
- Formateo PHP con PSR-12
- Nombres de variables y funciones en inglés
- Comentarios claros y uso de PHPDoc
- Estructuras condicionales y bucles optimizados
- Evitar consultas SQL innecesarias
- Uso seguro de consultas SQL (prepare)

---

## Instrucciones para Copilot
1. Generar código de plugins o snippets siempre respetando estándares de WordPress/WooCommerce.
2. Usar hooks de WordPress y WooCommerce antes de modificar núcleo.
3. Incluir sanitización, escape de datos y nonces para seguridad.
4. Mantener código modular y fácil de mantener.
5. Documentar funciones y clases con PHPDoc.
6. Preparar todo código para producción y compatibilidad con futuras versiones.
7. Cuando se trabaje con WooCommerce, utilizar sus funciones y APIs en lugar de reescribir lógica interna.

---

## Nota
Este perfil puede actualizarse cuando se aprueben nuevas librerías, frameworks o metodologías.
Nombre interno: Perfil Desarrollador Web - WordPress WooCommerce
