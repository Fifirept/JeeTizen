/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* Réorganisation des commandes par drag & drop */
$("#table_cmd").sortable({
	axis: "y",
	cursor: "move",
	items: ".cmd",
	placeholder: "ui-state-highlight",
	tolerance: "intersect",
	forcePlaceholderSize: true
})

/* ============================================================
 * Logique dynamique des champs équipement
 * ============================================================ */

// Afficher/masquer les options selon le modèle TV
function updateModelFields() {
	var modele = $('.eqLogicAttr[data-l2key=modele_tv]').value()
	var subModele = $('.eqLogicAttr[data-l2key=sub_modele_tv]').value()

	if (modele === 'legacy') {
		$('#id_sub_modele_tv_group').hide()
		$('#id_sub_modele_delay').hide()
		$('#ssl_tv').hide()
	} else {
		$('#id_sub_modele_tv_group').show()
		$('#ssl_tv').show()
		// Afficher délai uniquement pour modèle K
		if (subModele === '1') {
			$('#id_sub_modele_delay').show()
		} else {
			$('#id_sub_modele_delay').hide()
		}
	}
}

// Afficher/masquer les options WOL
function updateWolFields() {
	var wolEnabled = $('.eqLogicAttr[data-l2key=wol_tv]').is(':checked')
	if (wolEnabled) {
		$('.wol_options').show()
		var wolDirect = $('input[data-l2key=wol_tv_direct]').is(':checked')
		var wolBroadcast = $('input[data-l2key=wol_tv_broadcast]').is(':checked')
		if (wolDirect) {
			$('.wol_direct_options').show()
		} else {
			$('.wol_direct_options').hide()
		}
		if (wolBroadcast) {
			$('.wol_broadcast_options').show()
		} else {
			$('.wol_broadcast_options').hide()
		}
	} else {
		$('.wol_options').hide()
		$('.wol_direct_options').hide()
		$('.wol_broadcast_options').hide()
	}
}

// Événements de changement
$('.eqLogicAttr[data-l2key=modele_tv]').on('change', updateModelFields)
$('.eqLogicAttr[data-l2key=sub_modele_tv]').on('change', updateModelFields)
$('#chk_wol_tv').on('change', updateWolFields)
$('input[name=wol_type]').on('change', updateWolFields)

// Appliquer à l'affichage d'un équipement (event Jeedom)
$('body').off('JeeTizen_printEqLogic').on('JeeTizen_printEqLogic', function () {
	updateModelFields()
	updateWolFields()
})

/* ============================================================
 * Bouton de réinitialisation du token
 * ============================================================ */
$('#bt_resetToken').off('click').on('click', function () {
	var eqId = $('.eqLogicAttr[data-l1key=id]').value()
	if (!eqId || eqId === '') {
		$('#div_alert').showAlert({ message: '{{Veuillez d\'abord sauvegarder l\'équipement}}', level: 'warning' })
		return
	}
	bootbox.confirm('{{Êtes-vous sûr de vouloir réinitialiser le token ? Vous devrez accepter à nouveau l\'appairage sur la TV.}}', function (result) {
		if (result) {
			$.ajax({
				type: 'POST',
				url: 'plugins/JeeTizen/core/ajax/JeeTizen.ajax.php',
				data: {
					action: 'resetToken',
					id: eqId,
					jeedom_token: JEEDOM_AJAX_TOKEN
				},
				dataType: 'json',
				error: function (request, status, error) {
					handleAjaxError(request, status, error)
				},
				success: function (data) {
					if (data.state != 'ok') {
						$('#div_alert').showAlert({ message: data.result, level: 'danger' })
						return
					}
					$('#div_alert').showAlert({ message: '{{Token réinitialisé avec succès}}', level: 'success' })
					$('.eqLogicAttr[data-l2key=tokenAuth]').value('')
				}
			})
		}
	})
})

/* ============================================================
 * Affichage des commandes dans l'onglet Commandes
 * ============================================================ */
function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = { configuration: {} }
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {}
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
	tr += '<td class="hidden-xs">'
	tr += '<span class="cmdAttr" data-l1key="id"></span>'
	tr += '</td>'
	tr += '<td>'
	tr += '<div class="input-group">'
	tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
	tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
	tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
	tr += '</div>'
	tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
	tr += '<option value="">{{Aucune}}</option>'
	tr += '</select>'
	tr += '</td>'
	tr += '<td>'
	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
	tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
	tr += '</td>'
	tr += '<td>'
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
	tr += '<div style="margin-top:7px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '</div>'
	tr += '</td>'
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
	tr += '</td>';
	tr += '<td>'
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
	}
	tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
	tr += '</tr>'
	$('#table_cmd tbody').append(tr)
	var tr = $('#table_cmd tbody tr').last()
	jeedom.eqLogic.buildSelectCmd({
		id: $('.eqLogicAttr[data-l1key=id]').value(),
		filter: { type: 'info' },
		error: function (error) {
			$('#div_alert').showAlert({ message: error.message, level: 'danger' })
		},
		success: function (result) {
			tr.find('.cmdAttr[data-l1key=value]').append(result)
			tr.setValues(_cmd, '.cmdAttr')
			jeedom.cmd.changeType(tr, init(_cmd.subType))
		}
	})
}
