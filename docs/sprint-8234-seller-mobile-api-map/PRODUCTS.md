# Produtos

`GET /products` usa `ProductCatalogService::activeCatalogForSeller` + `isSellable()`.

Inativos e sem estoque (quando `stock_control`) não aparecem.

Campos: id, name, description, price, stock, commission preview já persistida no produto, image se existir.

Fluxo “Apresentar” (Blade) permanece WEB-ONLY.
