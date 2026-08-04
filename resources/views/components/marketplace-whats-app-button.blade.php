@props(['settings', 'context' => null])

@php
    $link = $settings->whatsappLink($context);
@endphp

@if($settings->hasWhatsAppButton() && $link)
    <style>
        @keyframes mkp-wa-pulse {
            0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.55); }
            70% { box-shadow: 0 0 0 14px rgba(37, 211, 102, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
        }
        .mkp-whatsapp {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 220;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #25D366;
            color: #fff;
            display: grid;
            place-items: center;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
            animation: mkp-wa-pulse 2.2s ease-out infinite;
            text-decoration: none;
        }
        .mkp-whatsapp:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 32px rgba(37, 211, 102, 0.5);
        }
        @media (max-width: 480px) {
            .mkp-whatsapp {
                width: 52px;
                height: 52px;
                bottom: 1rem;
                right: 1rem;
            }
        }
    </style>
    <a
        href="{{ $link }}"
        class="mkp-whatsapp"
        target="_blank"
        rel="noopener noreferrer"
        data-mkp-event="marketplace.whatsapp_clicked"
        aria-label="Falar no WhatsApp"
    >
        <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" fill="currentColor">
            <path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.15 1.6 5.96L0 24l6.3-1.65a11.9 11.9 0 0 0 5.76 1.47h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.44zM12.07 21.15h-.01a9.24 9.24 0 0 1-4.71-1.29l-.34-.2-3.74.98 1-3.64-.22-.37a9.23 9.23 0 0 1-1.42-4.93c0-5.1 4.15-9.25 9.26-9.25a9.2 9.2 0 0 1 6.55 2.71 9.2 9.2 0 0 1 2.7 6.55c0 5.1-4.15 9.24-9.24 9.24zm5.08-6.92c-.28-.14-1.65-.81-1.9-.9-.26-.1-.44-.14-.63.14-.18.28-.72.9-.88 1.08-.16.18-.33.2-.6.07-.28-.14-1.17-.43-2.23-1.37-.82-.73-1.38-1.64-1.54-1.92-.16-.28-.02-.43.12-.57.13-.12.28-.33.42-.49.14-.16.18-.28.28-.46.09-.18.05-.35-.02-.49-.07-.14-.63-1.52-.86-2.08-.23-.55-.46-.47-.63-.48h-.54c-.18 0-.49.07-.74.35-.26.28-.97.95-.97 2.3s.99 2.67 1.13 2.85c.14.18 1.95 2.98 4.72 4.18.66.28 1.18.45 1.58.58.66.21 1.27.18 1.75.11.53-.08 1.65-.67 1.88-1.32.23-.65.23-1.2.16-1.32-.07-.11-.25-.18-.53-.32z"/>
        </svg>
    </a>
@endif
