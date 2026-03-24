/* JeeTizen - comportements spécifiques */
// plugin.template.js (chargé après) gère : liste équipements, add, save, remove

// Réinitialiser le token
$('#bt_clearToken').on('click', function () {
    $('.eqLogicAttr[data-l2key="tokenAuth"]').val('');
    $('#div_alert').showAlert({
        message: '{{Token réinitialisé — la TV demandera une nouvelle autorisation}}',
        level: 'warning'
    });
});

// Affichage de la ligne de commande dans le tableau
function addCmdToTable(_cmd) {
    if (!_cmd) { _cmd = {}; }
    if (!_cmd.configuration) { _cmd.configuration = {}; }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="name"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="subType"></span>';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(init(_cmd.id))) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
    }
    tr += '<a class="btn btn-danger btn-xs cmdAction" data-action="remove"><i class="fa fa-minus-circle"></i></a>';
    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
}
