jQuery(document).ready(function($) {
    /**
     * Abre la factura de YGLU en una pestaña nueva desde el listado de Mi cuenta.
     *
     * @param {JQuery} $links Enlaces de acción "Factura".
     * @returns {void}
     */
    function ygeOpenInvoiceInNewTab($links) {
        $links.attr({
            target: '_blank',
            rel: 'noopener'
        });
    }

    ygeOpenInvoiceInNewTab($('a.woocommerce-button.invoice'));
});
