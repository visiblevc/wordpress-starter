// global vars
var hooks = [];
var call_stack = [];
var non_fatal_errors = '';
var migration_error = false;
var connection_data;
var next_step_in_migration;

function wpsdb_call_next_hook() {
  if (!call_stack.length) {
    call_stack = hooks;
  }

  var func = call_stack[0];
  call_stack.shift();
  window[func](); // Uses the string from the array to call the function of the same name
}

function wpsdb_add_commas(number_string) {
  number_string += '';
  x = number_string.split('.');
  x1 = x[0];
  x2 = x.length > 1 ? '.' + x[1] : '';
  var rgx = /(\d+)(\d{3})/;
  while (rgx.test(x1)) {
    x1 = x1.replace(rgx, '$1' + ',' + '$2');
  }
  return x1 + x2;
}
