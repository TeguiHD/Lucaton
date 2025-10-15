<?php

return [
    [
        'key' => 'primeros-pasos',
        'title' => 'Primeros pasos',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'questions' => [
            [
                'id' => 'que-es-lucaton',
                'question' => '¿Qué es Lucatón?',
                'answer' => 'Lucatón es una plataforma de crowdfunding solidario que conecta a personas con causas sociales importantes. Permitimos crear campañas para recaudar fondos destinados a proyectos educativos, de salud, ambientales y de ayuda comunitaria.'
            ],
            [
                'id' => 'como-funciona-lucaton',
                'question' => '¿Cómo funciona Lucatón?',
                'answer' => 'Los creadores publican su campaña con una meta de recaudación, las personas colaboran con el monto que deseen y reciben actualizaciones del avance. Cuando la campaña alcanza su meta y proceso de validación, se liberan los fondos al creador para ejecutar el proyecto.'
            ],
            [
                'id' => 'verificacion-correo',
                'question' => '¿Cómo verifico mi correo en Lucatón?',
                'answer' => 'Luego de registrarte te enviaremos un mensaje al correo indicado. Haz clic en el enlace de validación para activar tu cuenta. Si el enlace caducó, ingresa a tu perfil y solicita uno nuevo. Revisa las carpetas de spam o promociones si no recibes el mensaje en unos minutos.'
            ],
            [
                'id' => 'proyectos-admitidos',
                'question' => '¿Qué tipos de proyectos se permiten?',
                'answer' => 'Admitimos proyectos solidarios: educación, salud, medio ambiente, emergencias y ayuda comunitaria. No se aceptan campañas con fines comerciales, partidistas ni actividades que contravengan las políticas públicas vigentes.'
            ],
        ],
    ],
    [
        'key' => 'cuentas-y-roles',
        'title' => 'Cuentas y roles',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87M16 11a4 4 0 10-8 0 4 4 0 008 0z" />',
        'questions' => [
            [
                'id' => 'roles-permisos',
                'question' => '¿Qué roles y permisos existen?',
                'answer' => 'Existen cuatro perfiles principales. El creador administra la campaña y puede publicar actualizaciones. Los colaboradores ayudan con publicaciones sin poder retirar fondos. Los donantes aportan y reciben novedades. El equipo administrador modera campañas, revisa reportes y ejecuta auditorías.'
            ],
            [
                'id' => 'editar-perfil',
                'question' => '¿Puedo editar mi perfil después de registrarme?',
                'answer' => 'Sí. Desde la sección Perfil puedes actualizar tus datos personales, subir una foto, modificar preferencias de notificación y gestionar la seguridad de tu cuenta.'
            ],
            [
                'id' => 'autenticacion-dos-factores',
                'question' => '¿Lucatón ofrece autenticación en dos pasos?',
                'answer' => 'El prototipo simula un flujo de verificación adicional. Actívalo desde Perfil > Seguridad para añadir un código temporal a tu contraseña al iniciar sesión.'
            ],
        ],
    ],
    [
        'key' => 'campanas',
        'title' => 'Campañas',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
        'questions' => [
            [
                'id' => 'crear-campana',
                'question' => '¿Cómo creo una campaña?',
                'answer' => 'Regístrate, valida tu identidad y completa el formulario de creación con título, descripción, meta de recaudación, imágenes de respaldo y documentación. Nuestro equipo valida la información en menos de 24 horas hábiles.'
            ],
            [
                'id' => 'duracion-campana',
                'question' => '¿Cuánto tiempo puede durar una campaña?',
                'answer' => 'Las campañas duran entre 30 y 90 días. Recomendamos escoger 60 días para organizar la difusión. Puedes extenderla una vez por 30 días adicionales si cumple los requisitos.'
            ],
            [
                'id' => 'modelo-todo-o-nada',
                'question' => '¿Qué pasa si no alcanzo mi meta?',
                'answer' => 'Trabajamos con el modelo todo o nada. Si no alcanzas la meta, los donantes reciben el reembolso automático y puedes reintentar más adelante con una propuesta ajustada.'
            ],
            [
                'id' => 'editar-campana',
                'question' => '¿Puedo editar mi campaña después de publicarla?',
                'answer' => 'Puedes agregar actualizaciones, imágenes y aclaraciones. La meta y el plazo permanecen fijos para proteger a los donantes, salvo excepciones autorizadas por el equipo de revisión.'
            ],
        ],
    ],
    [
        'key' => 'donaciones-y-pagos',
        'title' => 'Donaciones y pagos',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />',
        'questions' => [
            [
                'id' => 'pagos',
                'question' => '¿Qué métodos de pago aceptan?',
                'answer' => 'Aceptamos tarjetas de crédito o débito nacionales, transferencias bancarias y pagos vía Khipu. Las transacciones se procesan con proveedores certificados para resguardar tus datos.'
            ],
            [
                'id' => 'cancelar-donacion',
                'question' => '¿Puedo cancelar mi donación?',
                'answer' => 'Puedes solicitar la reversa durante las primeras 24 horas siempre que la campaña siga activa. Pasado ese plazo debes contactar al soporte para revisar el caso.'
            ],
            [
                'id' => 'retiro-de-fondos',
                'question' => '¿Cómo retiro los fondos recaudados?',
                'answer' => 'Al finalizar tu campaña, carga los comprobantes y coordina la transferencia desde la sección Finanzas. Las liberaciones demoran entre 5 y 7 días hábiles después de la validación.'
            ],
            [
                'id' => 'pago-no-aparece',
                'question' => 'Mi pago no aparece, ¿qué hago?',
                'answer' => 'Verifica si el banco autorizó el cobro, revisa tu correo para confirmar y compara con el historial de donaciones. Si sigue pendiente después de 24 horas, abre un ticket en la sección reportar un problema con el ID del pago.'
            ],
        ],
    ],
    [
        'key' => 'seguridad',
        'title' => 'Seguridad y soporte',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 11-9.75 9.75A9.75 9.75 0 0112 2.25z" />',
        'questions' => [
            [
                'id' => 'es-seguro-usar-lucaton',
                'question' => '¿Es seguro usar Lucatón?',
                'answer' => 'Sí. El prototipo implementa conexiones cifradas, validación de campañas y controles de acceso diferenciados. Todas las acciones relevantes quedan registradas para auditoría.'
            ],
            [
                'id' => 'reportar-campana-sospechosa',
                'question' => '¿Cómo reporto una campaña sospechosa?',
                'answer' => 'Utiliza el botón Reportar en la página de la campaña o completa el formulario en la sección reportar un problema. Indica el enlace y evidencias para que el equipo de moderación priorice el caso.'
            ],
            [
                'id' => 'soporte-contacto',
                'question' => '¿Cómo contacto al equipo de soporte?',
                'answer' => 'Escríbenos mediante el formulario de contacto o envía un correo a ' . PROJECT_OWNER_EMAIL . '. En escenarios críticos puedes abrir un ticket desde el Centro de ayuda para seguimiento en el panel administrativo.'
            ],
        ],
    ],
];
