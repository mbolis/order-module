(function($) {
  $.datepicker._checkOffset = function(inst, offset, isFixed) {
    var dpWidth = inst.dpDiv.outerWidth(),
      dpHeight = inst.dpDiv.outerHeight(),
      inputWidth = inst.input ? inst.input.outerWidth() : 0,
      inputHeight = inst.input ? inst.input.outerHeight() : 0,
      viewWidth = document.documentElement.clientWidth + (isFixed ? 0 : $(document).scrollLeft()),
      viewHeight = document.documentElement.clientHeight + (isFixed ? 0 : $(document).scrollTop());
    
    offset.left -= (this._get(inst, "isRTL") ? (dpWidth - inputWidth) : 0);
    offset.left -= (isFixed && offset.left === inst.input.offset().left) ? $(document).scrollLeft() : 0;
    offset.top -= (isFixed && offset.top === (inst.input.offset().top + inputHeight)) ? $(document).scrollTop() : 0;
    
    // now check if datepicker is showing outside window viewport - move to a better place if so.
    offset.left -= Math.min(offset.left, (offset.left + dpWidth > viewWidth && viewWidth > dpWidth) ?
    	Math.abs(offset.left + dpWidth - viewWidth) : 0);

    // XXX : Here be a missing line, because we don't like jQuery UI messing up
    //       with the vertical positioning of the datepicker. Sorry...
    
    return offset;
  };
}(jQuery));
