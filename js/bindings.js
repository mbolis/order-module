function Page(template) {
  this.template = template;
  this.onRender = function() {};
}

function SplashPage() {
  Page.call(this, 'splash');
  this.visited = false;
  this.valid = true;
}

var RE_TELEFONO = /^[-+0-9 \t()]+$/;

function ContactPage(gasList) {
  Page.call(this, 'contactInformation');
  this.cliente = {
    nome : ko.observable(''),
    indirizzo : ko.observable(''),
    telefono : ko.observable('')
  };
  this.cliente.telefonoIsValid = ko.computed(function() {
    var telefono = this.telefono().trim();
    return !telefono || RE_TELEFONO.test(telefono);
  }, this.cliente);
  this.isGasMember = ko.observable();
  this.gasList = ko.observableArray(gasList);
  this.id_gas = ko.observable();
  this.gas = ko.computed(function() {
    var gasList = this.gasList();
    var id = this.id_gas();
    for (var i in gasList) {
      var gas = gasList[i];
      if (gas.id === id) {
        return gas;
      }
    }
  }, this);
  this.valid = ko.computed(function() {
    if (!this.cliente.nome().trim()) {
      return false;
    }
    if (this.isGasMember()) {
      return !!this.id_gas();
    } else {
      return this.cliente.indirizzo().trim() && this.cliente.telefono() && this.cliente.telefonoIsValid();
    }
  }, this);
}

function ProductsPage(typology, products) {
  Page.call(this, 'products');
  this.typology = typology;
  this.title = typology[0].toUpperCase() + typology.slice(1);
  for (var i in products) {
    products[i] = new Product(products[i]);
  }
  this.products = products;
  this.valid = ko.computed(function() {
    var valid = true;
    for (var i in this.products) {
      valid &= this.products[i].valid();
    }
    return !!valid;
  }, this);
  this.subTotalFloat = ko.computed(function() {
    var sum = 0;
    for (var i in this.products) {
      var price = this.products[i].finalPriceFloat();
      if (!Number.isNaN(price)) {
        sum += price;
      }
    }
    return sum;
  }, this);
  this.subTotal = ko.computed(function() {
    return this.subTotalFloat().toFixed(2) + ' &euro;'
  }, this);
}

RE_QTY = /^\d*([.,]\d+)?$/;

function Product(p) {
  for (var k in p) {
    this[k] = p[k];
  }
  this.unitaMisuraAbbr = ko.computed(function() {
    return this.unita_misura.length <= 2 ? this.unita_misura : this.unita_misura[0];
  }, this);
  this.prezzoDisplay = ko.computed(function() {
    return Number(this.prezzo).toFixed(2).replace(/\./, ',');
  }, this);
  this.qty = ko.observable('');
  this.valid = ko.computed(function() {
    return RE_QTY.test(this.qty().trim());
  }, this);
  this.qtyFloat = ko.computed(function() {
    var qty = this.qty().trim();
    return qty ? parseFloat(qty.replace(/,/, '.')) : 0;
  }, this);
  this.finalPriceFloat = ko.computed(function() {
    return this.prezzo * this.qtyFloat();
  }, this);
  this.finalPrice = ko.computed(function() {
    var price = this.finalPriceFloat();
    return price ? price.toFixed(2) + ' &euro;' : '';
  }, this);
}

var username;

var splashPage, contactPage, productsPages = ko.observableArray([]), summaryPage, finalPage;
function SummaryPage() {
  Page.call(this, 'summary');
  this.username = ko.computed(function() {
    return username;
  });
  this.typologies = ko.computed(function() {
    return productsPages();
  });
  this.contacts = ko.computed(function() {
    return contactPage;
  });
  this.totalPrice = ko.computed(function() {
    var totalPrice = 0;
    var typologies = this.typologies();
    for (var t in typologies) {
      var products = typologies[t].products;
      for (var p in products) {
        totalPrice += products[p].finalPriceFloat();
      }
    }
    return totalPrice.toFixed(2) + ' &euro;';
  }, this);
}
SummaryPage.prototype.valid = true;

function FinalPage(result) {
  Page.call(this, 'final');
  this.result = result;
}
FinalPage.prototype.valid = true;
FinalPage.prototype.isFinal = true;

var viewModel = new ViewModel;
function ViewModel() {
  var page = ko.observable(0);

  var pages = ko.observableArray([
    splashPage = new SplashPage(),
    contactPage = new ContactPage([]),
    summaryPage = new SummaryPage()
  ]);

  this.currentPage = ko.computed(function() {
    return pages()[page()];
  }, this);
  this.isFirst = ko.computed(function() { return page() === 0 });
  this.isLast = ko.computed(function() { return page() === pages().length - 1 });

  function setPage(idx) {
    page(idx);
    var p = pages()[idx];
    location.hash = '#' + p.template + (p.typology ? '!t:' + p.typology : '');
  }
  this.next = function() {
    pages()[0].visited = true;
    setPage(page() + 1);
    window.scrollTo(0, $('.entry').prevAll('h2').offset().top - 32);
  };
  this.back = function() {
    setPage(page() - 1);
    window.scrollTo(0, $('.entry').prevAll('h2').offset().top - 32);
  };
  this.submit = function(viewModel) {
    viewModel.lock(true);

    var data = {
      action : 'order_form_submit',
      client : {
        username : username,
        note : this.notes(),
        nome : contactPage.cliente.nome()
      }
    };
    if (contactPage.id_gas()) {
      data.client.id_gas = contactPage.id_gas();
    } else {
      data.client.indirizzo = contactPage.cliente.indirizzo;
      data.client.telefono = contactPage.cliente.telefono;
    }

    var products = [], prodPages = productsPages();
    for (var t in prodPages) {
      var typology = prodPages[t].products;
      for (var p in typology) {
        var product = typology[p];
        if (product.qtyFloat()) {
          products.push({
            id_prodotto_ordine : product.id_prodotto_ordine,
            quantita : product.qtyFloat() });
        }
      }
    }
    data.products = products;

    jQuery.ajax({
      url : ajaxurl,
      type : 'POST',
      data : data,
      dataType : 'json',
      success : function(result) {
        pages.push(new FinalPage(result));
        setPage(page() + 1);
        viewModel.lock(false);
      },
      error : function(xhr, a, b, c) {
        pages.push(new FinalPage({status:-xhr.status}));
        setPage(page() + 1);
        viewModel.lock(false);
      }
    });
  };

  this.lock = ko.observable(false);

  this.grandTotal = ko.computed(function() {
    var sum = 0;
    var prodPages = productsPages();
    for (var i = 0; i < prodPages.length; i++) {
      sum += prodPages[i].subTotalFloat();
    }
    return sum.toFixed(2) + ' &euro;';
  }, this);

  this.notes = ko.observable('');

  var route = this.route = function() {
    var hash = location.hash;
    if (!hash) {
      location.hash = '#' + (hash = 'splash');
    }
    if (hash[0] === '#') {
      hash = hash.slice(1);
    }
    hash = decodeURIComponent(hash).split(/!/);
    var path = hash[0];
    var query = (hash[1] || '').split(/;/g);
    var params = {};
    for (var i in query) {
      var kv = query[i].split(/:/);
      params[kv[0]] = kv[1];
    }
  
    var ps = pages();
    if (!splashPage.visited) {
      return setPage(0);
    }
    for (var i = 0; i < ps.length; i++) {
      var p = ps[i];
      if (p.template === path) {
        if (path !== 'products' || p.typology === params.t) {
          page(i);
        }
      }
    }
  }

  route();

  window.loadGasList = function(data) {
    contactPage.gasList(data);
  }

  window.loadProducts = function(data) {
    productsPages.removeAll();
    for (var i in data) {
      var pp = data[i];
      if (pp[1].length) {
        productsPages.push(new ProductsPage(pp[0], pp[1]));
      }
    }
    pages([splashPage, contactPage].concat(productsPages()).concat(summaryPage));
    route();
  }
}
ko.applyBindings(viewModel);
window.addEventListener('popstate', viewModel.route);
