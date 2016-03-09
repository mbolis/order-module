<?php if($success) { ?>
  <div class="updated">
    <p>Modifiche salvate.</p>
  </div>
<?php } elseif ($errors) { ?>
  <div class="error">
    <p>Si &egrave; verificato un errore misterioso...</p>
  </div>
<?php } ?>
<div class="wrap">
  <h2>Prodotti</h2>
  <div class="postbox">
    <div class="inside">
      <div class="om-form-row">
        <label class="medium header"><strong>Nome</strong></label>
        <label class="small header"><strong>Tipologia</strong></label>
        <label class="small header"><strong>Unit&agrave;</strong></label>
        <label class="large header"><strong>Provenienza</strong></label>
        <label class="medium header"><strong>Pagina</strong></label>
      </div>
      <div data-bind="foreach: products">
        <div class="om-form-row">
          <input type="text" class="medium" data-bind="textInput: nome, disable: action()==='delete'">
          <select class="small" data-bind="value: tipologia, options: $root.typologies, disable: action()==='delete'"></select>
          <select class="small" data-bind="value: unita_misura, options: $root.units, disable: action()==='delete'"></select>
          <input type="text" data-bind="textInput: provenienza, disable: action()==='delete'">
          <select class="medium" data-bind="value: id_pagina, options: $root.pages, optionsValue: 'id', optionsText: 'title', optionsCaption: '- nessuna -', disable: action()==='delete'"></select>
          <!--ko if: action()!=='delete'--><label class="om-delete-product smallest" data-bind="click: remove" title="Elimina"></label><!--/ko-->
          <!--ko if: action()==='delete'--><label class="om-undelete-product smallest" data-bind="click: unremove" title="Annulla eliminazione"></label><!--/ko-->
        </div>
      </div>
      <div style="border-top:1px solid silver;text-align:center;color:#999"><em>Aggiungi prodotto:</em></div>
      <div class="om-form-row" data-bind="with: newProduct">
        <input type="text" class="medium" data-bind="textInput: nome">
        <select class="small" data-bind="value: tipologia, options: $root.typologies, optionsCaption: ''"></select>
        <select class="small" data-bind="value: unita_misura, options: $root.units, optionsCaption: ''"></select>
        <input type="text" data-bind="textInput: provenienza">
        <select class="medium" data-bind="value: id_pagina, options: $root.pages, optionsValue: 'id', optionsText: 'title', optionsCaption: '- nessuna -'"></select>
        <!--ko if: valid--><label class="om-add-product smallest" data-bind="click: submit" title="Aggiungi prodotto"></label><!--/ko-->
      </div>

      <form method="POST">
        <!--ko foreach: toUpdate-->
          <input type="hidden" data-bind="attr: { name: 'update['+$index()+'][id]' }, value: id">
          <input type="hidden" data-bind="attr: { name: 'update['+$index()+'][nome]' }, value: nome">
          <input type="hidden" data-bind="attr: { name: 'update['+$index()+'][tipologia]' }, value: tipologia">
          <input type="hidden" data-bind="attr: { name: 'update['+$index()+'][unita_misura]' }, value: unita_misura">
          <input type="hidden" data-bind="attr: { name: 'update['+$index()+'][unita_misura_plurale]' }, value: unita_misura_plurale">
          <input type="hidden" data-bind="attr: { name: 'update['+$index()+'][provenienza]' }, value: provenienza">
          <input type="hidden" data-bind="attr: { name: 'update['+$index()+'][id_pagina]' }, value: id_pagina">
        <!--/ko-->
        <!--ko foreach: toInsert-->
          <input type="hidden" data-bind="attr: { name: 'insert['+$index()+'][nome]' }, value: nome">
          <input type="hidden" data-bind="attr: { name: 'insert['+$index()+'][tipologia]' }, value: tipologia">
          <input type="hidden" data-bind="attr: { name: 'insert['+$index()+'][unita_misura]' }, value: unita_misura">
          <input type="hidden" data-bind="attr: { name: 'insert['+$index()+'][unita_misura_plurale]' }, value: unita_misura_plurale">
          <input type="hidden" data-bind="attr: { name: 'insert['+$index()+'][provenienza]' }, value: provenienza">
          <input type="hidden" data-bind="attr: { name: 'insert['+$index()+'][id_pagina]' }, value: id_pagina">
        <!--/ko-->
        <!--ko foreach: toDelete-->
          <input type="hidden" data-bind="attr: { name: 'delete['+$index()+'][id]' }, value: id">
        <!--/ko-->
        <input type="submit" class="button" name="submit" value="Salva" />
      </form>
    </div>
  </div>
</div>
<?php echo wp_enqueue_media(); ?>
<script>
  (function($) {
    $('.updated,.error').on('click', function() {
      $(this).remove();
    });
    $(function() {console.log(wp.media);});
  }(jQuery));
</script>
<script>
  var pages = [
    <?php if($pages) { $page = $pages[0]; ?>
      {
        id : <?php echo $page->ID; ?>,
        title : '<?php echo str_replace("'", "\\'", $page->post_title); ?>'
      }<?php } foreach($pages as $page) { ?>,
      {
        id : <?php echo $page->ID; ?>,
        title : '<?php echo str_replace("'", "\\'", $page->post_title); ?>'
      }
    <?php } ?>
  ];
  
  // Subscribe to track updates in product
  var keys = ['nome', 'tipologia', 'unita_misura', 'provenienza', 'id_pagina'];
  function updateModel(product) {
    return function(newVal) {
      for (var k in keys) {
        var key = keys[k];
        if (product[key].old != product[key]()) {
          product.action('update');
          return;
        }
      }
      product.action(undefined);
    }
  }
  function removeProduct(product) {
    return function() {
      product.action('delete');
    }
  }

  var units = <?php echo json_encode($units); ?>;
  var unitsPlural = <?php echo json_encode($units_plural); ?>;

  var products = <?php echo json_encode($products); ?>;
  for (var i in products) {
    var product = products[i];
    product.action = ko.observable();
    for (var k in keys) {
      var key = keys[k];
      var oldVal = product[key];
      product[key] = ko.observable(product[key]);
      product[key].old = oldVal;
      product[key].subscribe(updateModel(product));
    }
    product.unita_misura_plurale = ko.computed(function() { return unitsPlural[this.unita_misura()] }, product);
    product.remove = removeProduct(product);
    product.unremove = updateModel(product);
  }

  var newProduct = {
    nome : ko.observable(''),
    tipologia : ko.observable(''),
    unita_misura : ko.observable(''),
    provenienza : ko.observable(''),
    id_pagina : ko.observable(''),
    valid : ko.pureComputed(function() {
      return newProduct.nome().trim().length &&
             newProduct.tipologia() &&
             newProduct.unita_misura();
    }),
    submit : function() {
      if (!newProduct.valid()) {
        return;
      }

      var product = {
        nome : ko.observable(newProduct.nome()),
        tipologia : ko.observable(newProduct.tipologia()),
        unita_misura : ko.observable(newProduct.unita_misura()),
        provenienza : ko.observable(newProduct.provenienza()),
        id_pagina : ko.observable(newProduct.id_pagina()),
        action : function() { return 'insert' },
        remove : function() { viewModel.products.remove(product) }
      };
      product.unita_misura_plurale = ko.computed(function() { return unitsPlural[this.unita_misura()] }, product);
      viewModel.products.push(product);

      newProduct.nome('');
      newProduct.tipologia('');
      newProduct.unita_misura('');
      newProduct.provenienza('');
      newProduct.id_pagina('');
    }
  };

  var viewModel = {
    products : ko.observableArray(products),
    newProduct : ko.observable(newProduct),
    typologies : <?php echo json_encode($typologies); ?>,
    units : <?php echo json_encode($units); ?>,
    pages : pages,
    toUpdate : ko.pureComputed(selectProductsTo('update')),
    toInsert : ko.pureComputed(selectProductsTo('insert')),
    toDelete : ko.pureComputed(selectProductsTo('delete'))
  };

  function selectProductsTo(action) {
    return function() {
      var products = viewModel.products(), toUpdate = [];
      for (var i in products) {
        var product = products[i];
        if (product.action() === action) {
          toUpdate.push(product);
        }
      }
      return toUpdate;
    };
  }
  ko.applyBindings(viewModel);
</script>
