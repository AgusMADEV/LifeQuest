# Evolución del proyecto

Este documento explica la evolución de LifeQuest como una secuencia de hitos reales del repositorio, no como una propuesta teórica.

## Fase 1: Base funcional

- Estructura web principal en `public/`.
- Autenticación de usuario.
- CRUDs base para áreas, metas, retos, misiones y hábitos.
- Esquema inicial de base de datos.

## Fase 2: Gamificación básica

- XP y niveles como eje principal del progreso.
- Rachas y métricas de actividad.
- Evolución visible en dashboard y progreso.
- Recompensas asociadas a acciones del usuario.

## Fase 3: Separación de dominios

- Unificación de metas, retos y misiones bajo una sola pantalla operativa.
- Normalización de nombres de dominio entre base de datos, backend y vistas.
- Consolidación de los controladores y modelos por responsabilidad.

## Fase 4: Extensión de la progresión

- HP global del personaje.
- Hábitos de riesgo con impacto en HP.
- Progresión por área.
- Objetivo diario con bonus persistente.

## Fase 5: Tienda y colección

- Tienda pública separada del resto del flujo.
- Indulgencias como primeras recompensas canjeables.
- Inventario cosmético y equipamiento por categoría.
- Soporte de imágenes de recompensa.

## Fase 6: Administración y mantenimiento

- Panel administrativo independiente.
- Exploración de base de datos y utilidades de administración.
- Migraciones y rollbacks para las distintas ampliaciones del proyecto.

## Cómo leer la evolución

La evolución del proyecto se entiende mejor como un crecimiento por capas:

1. Primero se resolvió la gestión principal de productividad.
2. Después se añadió la gamificación.
3. A continuación se separaron módulos y pantallas para mantener el código más claro.
4. Por último se añadieron sistemas auxiliares de progreso, tienda y administración.

Si en el futuro se incorpora una funcionalidad nueva, este documento debe actualizarse junto con el código y la documentación principal.