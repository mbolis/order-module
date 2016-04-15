function Page(template) {
  this.template = template;
}
Page.prototype.valid = true;

function ContactPage(gasList) {
  Page.call(this, 'contactInformation');
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
    if (this.isGasMember()) {
      return !!this.id_gas();
    } else {
      return false; // TODO
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
    return valid;
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
    return this.subTotalFloat().toFixed(2) + '&euro;'
  }, this);
}

RE_QTY = /^\d*([.,]\d+)?$/;

function Product(p) {
  for (var k in p) {
    this[k] = p[k];
  }
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
    return price ? price.toFixed(2) + '&euro;' : '';
  }, this);
}

function SummaryPage(pages) {
  Page.call(this, 'summary');
  this.typologies = ko.computed(function() {
    var ps = pages.slice(2);
    return ps.slice(0, ps.length - 1);
  }, this);
}
SummaryPage.prototype.valid = true;

function ViewModel() {
  var page = ko.observable(0);
  var pages = ko.observableArray([
    new Page('splash'),
    new ContactPage([])
  ]);
  pages.push(new SummaryPage(pages));

  this.currentPage = ko.computed(function() {
    return pages()[page()];
  }, this);
  this.isFirst = ko.computed(function() { return page() === 0 });
  this.isLast = ko.computed(function() { return page() === pages().length - 1 });

  this.next = function() {
    var idx = page() + 1
    page(idx);

    var p = pages()[idx];
    location.hash = '#' + p.template + (p.typology ? '!t:' + p.typology : '');
  };
  this.back = function() {
    var idx = page() - 1
    page(idx);

    var p = pages()[idx];
    location.hash = '#' + p.template + (p.typology ? '!t:' + p.typology : '');
  };
  this.submit = function() {
    var form = document.getElementById('om_order_form');
    form.action = ajaxurl; // set from PHP side
    form.submit();
  };

  this.grandTotal = ko.computed(function() {
    var sum = 0;
    var selectedPages = pages.slice(2);
    for (var i = 0; i < selectedPages.length - 1; i++) {
      sum += selectedPages[i].subTotalFloat();
    }
    return sum.toFixed(2) + '&euro;';
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
    for (var i = 0; i < ps.length; i++) {
      var p = ps[i];
      if (p.template === path) {
        if (path !== 'products' || p.typology === params.t) {
          page(i);
        }
      }
    }
  }

  window.loadGasList = function(data) {
    var contactPage = pages()[1];
    contactPage.gasList(data);
  }

  window.loadProducts = function(data) {
    var removedPages = pages.splice(2);
    for (var i in data) {
      var pp = data[i];
      if (pp[1].length) {
        pages.push(new ProductsPage(pp[0], pp[1]));
      }
    }
    pages.push(removedPages[removedPages.length - 1]);
    route();
  }
}
var viewModel = new ViewModel;
viewModel.route();
ko.applyBindings(viewModel);

window.addEventListener('popstate', viewModel.route);
