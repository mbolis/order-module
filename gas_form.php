<div class="wrap">
  <div id="col-container">
    <h2>GAS</h2>
    <div id="notify_success" class="updated" style="display:none"><p></p></div>
    <div id="notify_error" class="error" style="display:none"><p></p></div>
    <div id="col-left" style="display:inline-block">
      <div class="om-gas-list">
        <ul data-bind="foreach: gasData">
          <li data-bind="click: select"><!--ko text: nome--><!--/ko--> (<!--ko text: area--><!--/ko-->)</li>
        </ul>
        <button class="om-add-gas" data-bind="click: add, disable: selectedGAS() && !selectedGAS().id">Aggiungi</button>
      </div>
    </div>
    <div id="col-right" data-bind="if: selectedGAS">
      <div class="om-gas-card" data-bind="with: selectedGAS">
        <div class="om-form-row">
          <label for="gas_nome" style="width:8em">Nome:</label>
          <input type="text" id="gas_nome" style="width:20em" data-bind="textInput: nome, css: {'om-error': nome.valid}">
        </div>
        <div class="om-form-row">
          <label for="gas_area" style="width:8em">Area:</label>
          <input type="text" id="gas_area" style="width:20em" data-bind="textInput: area">
        </div>
        <div class="om-form-row">
          <label for="gas_nome_contatto" style="width:8em">Referente:</label>
          <input type="text" id="gas_nome_contatto" style="width:20em" data-bind="textInput: nome_contatto">
        </div>
        <div class="om-form-row">
          <label for="gas_indirizzo" style="width:8em;vertical-align:top">Indirizzo:</label>
          <textarea id="gas_indirizzo" style="width:20em" data-bind="textInput: indirizzo"></textarea>
        </div>
        <div class="om-form-row">
          <label for="gas_telefono" style="width:8em">Telefono:</label>
          <input type="text" id="gas_telefono" style="width:20em" data-bind="textInput: telefono">
        </div>
        <!--ko if: valid--><button data-bind="click: save">Salva</button><!--/ko-->
        <button data-bind="click: remove">Elimina</button>
      </div>
    </div>
  </div>
</div>
<script>
  var selectedGAS = ko.observable();

  function modelProperty(initValue) {
    var value = ko.observable(initValue);
    var property = ko.computed({
      read : function() { return value() },
      write : function(newValue) {
        if (newValue) {
          value(newValue);
          property.valid(true);
        } else {
          property.valid(false);
        }
      }
    });
    property.valid = ko.observable(true);
    property.old = initValue;
    return property;
  }

  var viewModel = {
    gasData : ko.observableArray(),
    selectedGAS : selectedGAS,
    add : function() {
      viewModel.selectedGAS(new UpdatableGAS);
    }
  };
  var gasData = <?php echo json_encode($gas_data); ?>;
  for (var i in gasData) {
    viewModel.gasData.push(new GAS(gasData[i]));
  }
  ko.applyBindings(viewModel);

  function GAS(data) {
    for (var k in data) {
      this[k] = data[k];
    }
    this.select = function(gas) {
      selectedGAS(new UpdatableGAS(gas));
    }
  }

  var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

  function UpdatableGAS(original) {
    this.original = original;

    var data = original || {};
    this.id = data.id;
    this.nome = ko.observable(data.nome);
    this.area = ko.observable(data.area);
    this.nome_contatto = ko.observable(data.nome_contatto);
    this.indirizzo = ko.observable(data.indirizzo);
    this.telefono = ko.observable(data.telefono);

    this.valid = ko.computed(function() {
      return this.nome() &&
             this.area() &&
             this.nome_contatto() &&
             this.indirizzo() &&
             this.telefono();
    }, this);
  }
  UpdatableGAS.prototype.toJS = function() {
    return {
      id : this.id,
      nome : this.nome(),
      area : this.area(),
      nome_contatto : this.nome_contatto(),
      indirizzo : this.indirizzo(),
      telefono : this.telefono()
    }
  };
  UpdatableGAS.prototype.save = function() {
    var original = this.original;
    var gas = this.toJS();
    jQuery.ajax({
      url : ajaxurl,
      method : 'POST',
      dataType : 'json',
      data : {
        action : 'om_save_gas',
        gas : gas
      },
      success : function(id) {
        jQuery('#notify_success').show().find('p').text('"' + gas.nome + '" salvato.');
        jQuery('#notify_error').hide();

        selectedGAS(null);

        gas.id = id;
        var oldGas = ko.utils.arrayFirst(viewModel.gasData(), function(g) { return g.id === id});
        if (oldGas) {
          viewModel.gasData.replace(oldGas, new GAS(gas));
        } else {
          viewModel.gasData.push(new GAS(gas));
        }
      },
      error : function(xhr, err) {
        jQuery('#notify_success').hide();
        jQuery('#notify_errore').show().find('p').text('Impossibile salvare "' + original.nome + '".');
        console.log('Errore salvataggio <' + (original ? original.id : id) + '> : ' + err);
      }
    });
  };
  UpdatableGAS.prototype.remove = function() {
    var original = this.original;
    if (!original) {
      return selectedGAS(null);
    }
    
    if (confirm('Eliminare definitivamente "' + original.nome + '"?')) {
      jQuery.ajax({
        url : ajaxurl,
        method : 'POST',
        data : {
          action : 'om_delete_gas',
          id : original.id
        },
        success : function() {
          jQuery('#notify_success').show().find('p').text('"' + original.nome + '" eliminato.');
          jQuery('#notify_error').hide();
          viewModel.gasData.remove(original);
          selectedGAS(null);
        },
        error : function(xhr, err) {
          jQuery('#notify_success').hide();
          jQuery('#notify_errore').show().find('p').text('Impossibile eliminare "' + original.nome + '".');
          console.log('Errore eliminazione <' + original.id + '> : ' + err);
        }
      });
    }
  };
</script>
