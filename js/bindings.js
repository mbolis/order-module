function Page(template) {
  this.template = template;
}
Page.prototype.valid = true;

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

function ViewModel() {
  var page = ko.observable(0);
  var pages = ko.observableArray([
    new Page('splash'),
    new Page('contactInformation'),
    new Page('summary')
  ]);

  this.currentPage = ko.computed(function() {
    return pages()[page()];
  }, this);
  this.isFirst = ko.computed(function() { return page() === 0 });
  this.isLast = ko.computed(function() { return page() === pages().length - 1 });

  this.next = function() { page(page() + 1) };
  this.back = function() { page(page() - 1) };
  this.submit = function() {};

  this.grandTotal = ko.computed(function() {
    var sum = 0;
    var selectedPages = pages.slice(2);
    for (var i = 0; i < selectedPages.length - 1; i++) {
      sum += selectedPages[i].subTotalFloat();
    }
    return sum.toFixed(2) + '&euro;';
  }, this);

  window.loadProducts = function(data) {
    var removedPages = pages.splice(2);
    for (var i in data) {
      var pp = data[i];
      pages.push(new ProductsPage(pp[0], pp[1]));
    }
    pages.push(removedPages[removedPages.length - 1]);
  }
}
var viewModel = new ViewModel;
ko.applyBindings(viewModel);

