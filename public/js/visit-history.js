/**
 * Histórico de atendimentos — modal de detalhes (padrão Agenda).
 * Dados vêm de #history-cards-data (application/json), não de data-card.
 */
(function () {
    function boot() {
        const modal = document.getElementById('history-detail-modal');
        const dataEl = document.getElementById('history-cards-data');
        if (!modal || !dataEl) return;

        let cards = {};
        try {
            cards = JSON.parse(dataEl.textContent || '{}') || {};
        } catch (e) {
            console.error('Histórico: falha ao ler dados dos atendimentos.', e);
            return;
        }

        const fields = {
            client: document.getElementById('hd-client'),
            phone: document.getElementById('hd-phone'),
            address: document.getElementById('hd-address'),
            campaign: document.getElementById('hd-campaign'),
            visited: document.getElementById('hd-visited'),
            seller: document.getElementById('hd-seller'),
            result: document.getElementById('hd-result'),
            notes: document.getElementById('hd-notes'),
            plan: document.getElementById('hd-plan'),
            planWrap: document.getElementById('hd-plan-wrap'),
            next: document.getElementById('hd-next'),
            subtitle: document.getElementById('history-detail-subtitle'),
            wa: document.getElementById('hd-whatsapp'),
            route: document.getElementById('hd-route'),
            mapLink: document.getElementById('hd-map'),
            newReturn: document.getElementById('hd-new-return'),
        };

        function dash(value) {
            if (value == null) return '—';
            const text = String(value).trim();
            return text !== '' ? text : '—';
        }

        function resolveCard(visitId) {
            if (cards[visitId]) return cards[visitId];
            if (cards[String(visitId)]) return cards[String(visitId)];
            const asNum = Number(visitId);
            if (!Number.isNaN(asNum) && cards[asNum]) return cards[asNum];
            return null;
        }

        function openDetail(card) {
            if (!card) return;

            fields.subtitle.textContent = card.visited_at_full || card.visited_at || '';
            fields.client.textContent = dash(card.client);
            fields.phone.textContent = dash(card.phone);
            fields.address.textContent = dash(card.address);
            fields.campaign.textContent = dash(card.campaign);
            fields.visited.textContent = dash(card.visited_at_full || card.visited_at);
            fields.seller.textContent = dash(card.seller);
            fields.result.textContent = dash(card.result);
            fields.notes.textContent = dash(card.notes);
            fields.next.textContent = dash(card.next_action);

            const hasPlan = card.plan != null && String(card.plan).trim() !== '';
            if (fields.planWrap) {
                fields.planWrap.hidden = !hasPlan;
            }
            fields.plan.textContent = hasPlan ? card.plan : '—';

            if (card.wa_href) {
                fields.wa.href = card.wa_href;
                fields.wa.hidden = false;
            } else {
                fields.wa.removeAttribute('href');
                fields.wa.hidden = true;
            }

            if (card.route_href) {
                fields.route.href = card.route_href;
                fields.route.hidden = false;
            } else {
                fields.route.removeAttribute('href');
                fields.route.hidden = true;
            }

            fields.mapLink.href = card.map_url || '#';
            fields.newReturn.href = card.new_follow_up_url || '#';

            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeDetail() {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('.history-detail-btn').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const card = resolveCard(btn.getAttribute('data-visit-id'));
                if (!card) {
                    console.error('Histórico: atendimento não encontrado.', btn.getAttribute('data-visit-id'));
                    return;
                }
                openDetail(card);
            });
        });

        document.querySelectorAll('[data-history-close]').forEach((el) => {
            el.addEventListener('click', closeDetail);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeDetail();
            }
        });

        // Clique no painel não fecha; só backdrop
        modal.querySelector('.history-modal-panel')?.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
