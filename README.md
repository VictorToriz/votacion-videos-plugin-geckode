
# Concurso de Videos Geckode

**Versión:** 3.0  
**Autor:** [Geckode](https://geckode.com.mx)  
**Descripción:** Plugin para crear y gestionar un concurso de videos en WordPress con sistema de votación.

## 📦 Descripción

Este plugin permite publicar un concurso de videos en tu sitio WordPress, donde los visitantes pueden votar por sus favoritos. Se puede personalizar el título, descripción, fecha de finalización, y los videos participantes directamente desde la configuración del plugin.

## 🔧 Características

- Votación pública de videos
- Edición de título y descripción del concurso
- Definición de una fecha de cierre para la votación
- Soporte para hasta 6 videos por defecto
- Datos de ejemplo precargados al activarlo

## 🚀 Instalación

1. Sube la carpeta del plugin al directorio `/wp-content/plugins/`.
2. Activa el plugin desde el panel de administración de WordPress en la sección **Plugins**.
3. Configura los parámetros desde la sección correspondiente en el administrador de WordPress.

## 🛠️ Uso

- Utiliza el shortcode `[honeywhale_concurso]` en cualquier entrada o página para mostrar el concurso de videos.
- Personaliza los campos del concurso desde la configuración en el administrador.
- Los votos son públicos y se almacenan automáticamente.

## 📅 Configuración por Defecto

Al instalar el plugin se configuran automáticamente:

- Fecha de cierre del concurso: 1 mes desde la activación.
- Título del concurso: *Concurso de Videos HoneyWhale*
- Descripción: *Vota por tus videos favoritos. La votación termina en:*
- 3 videos de ejemplo precargados con su título, autor y URL.

## 🧩 Shortcode

```plaintext
[honeywhale_concurso]
```

## 🧼 Desinstalación

El plugin incluye un hook de desinstalación para eliminar sus opciones de la base de datos de forma automática.

## 📝 Licencia

MIT License

Copyright (c) 2025 Geckode

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the “Software”), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
