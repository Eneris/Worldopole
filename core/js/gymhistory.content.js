/** global: gymName */

var gymRanks = [
	{ level : 1,  prestigeMax : 2000,  prestigeMin : 0 },
	{ level : 2,  prestigeMax : 4000,  prestigeMin : 2000 },
	{ level : 3,  prestigeMax : 8000,  prestigeMin : 4000 },
	{ level : 4,  prestigeMax : 12000, prestigeMin : 8000 },
	{ level : 5,  prestigeMax : 16000, prestigeMin : 12000 },
	{ level : 6,  prestigeMax : 20000, prestigeMin : 16000 },
	{ level : 7,  prestigeMax : 30000, prestigeMin : 20000 },
	{ level : 8,  prestigeMax : 40000, prestigeMin : 30000 },
	{ level : 9,  prestigeMax : 50000, prestigeMin : 40000 },
	{ level : 10, prestigeMax : 52000, prestigeMin : 50000 }
];

$(function () {

	$.getJSON("core/json/variables.json", function(variables) {
		var pokeimg_suffix = variables['system']['pokeimg_suffix'];

		$('.topShaverLoader').hide();
		$('.gymShaverLoader').hide();
		$('.gymLoader').hide();

		var pageShaver = 0;
		var page = 0;
		var teamSelector = ''; //''=all; 0=neutral; 1=Blue; 2=Red; 3=Yellow
		var rankingFilter = 0; //0=Level & Gyms; 1=Level; 2=Gyms
		var gymHistoryLoaded = false;
		var topShaverLoaded = false;
		var gymShaverLoaded = false;

		$('input#name').filter(':visible').val(gymName);


		$('#loadMoreButton').click(function () {
			loadGyms(page, $('input#name').filter(':visible').val(), teamSelector, rankingFilter, pokeimg_suffix, true);
			page++;
		});

		$('#loadMoreButtonShaver').click(function () {
			loadGymShaver(pageShaver, $('input#name').filter(':visible').val(), teamSelector, rankingFilter, pokeimg_suffix);
			pageShaver++;
		});

		$('a[href="#gymHistory"]').click(function() {
			if (!gymHistoryLoaded) { $('#loadMoreButton').trigger('click'); }
			gymHistoryLoaded = true;
		}).trigger('click');

		$('a[href="#topShaver"]').click(function() {
			if (!topShaverLoaded) { loadTopShaver(); }
			topShaverLoaded = true;
		});

		$('a[href="#gymShaver"]').click(function() {
			if (!gymShaverLoaded) { $('#loadMoreButtonShaver').trigger('click'); }
			gymShaverLoaded = true;
		});

		$("#searchGyms").submit(function ( event ) {
			pageShaver = 0;
			page = 0;
			gymHistoryLoaded = false;
			gymShaverLoaded = false;
			$('#gymsContainer').empty();
			$('#gymShaverContainer').empty();
			if ($('a[href="#gymHistory"]').parent().hasClass('active')) {
				$('a[href="#gymHistory"]').trigger('click');
			} else if ($('a[href="#gymShaver"]').parent().hasClass('active')) {
				$('a[href="#gymShaver"]').trigger('click');
			}
			event.preventDefault();
		});

		$(".teamSelectorItems").click(function ( event ) {
			switch ($(this).attr("id")) {
				case "NeutralTeamsFilter":
					teamSelector=0;
					break;
				case "BlueTeamFilter":
					teamSelector=1;
					break;
				case "RedTeamFilter":
					teamSelector=2;
					break;
				case "YellowFilter":
					teamSelector=3;
					break;
				default:
					teamSelector='';
			}
			$("#teamSelectorText").html($(this).html());
			event.preventDefault();
			$("#searchGyms").submit();
		});

		$(".rankingOrderItems").click(function ( event ) {
			switch ($(this).attr("id")) {
				case "changedFirst":
					rankingFilter=0;
					break;
				case "nameFirst":
					rankingFilter=1;
					break;
				case "prestigeFirst":
					rankingFilter=2;
					break;
				default:
					rankingFilter=0;
			}
			$("#rankingOrderText").html($(this).html());
			event.preventDefault();
			$("#searchGyms").submit();
		});

		window.onpopstate = function() {
			if (window.history.state && "gymhistory" === window.history.state.page) {
				$('#gymsContainer').empty();
				$('#gymShaverContainer').empty();
				$('input#name').filter(':visible').val(window.history.state.name);
				loadGymShaver(0, $('input#name').filter(':visible').val(), teamSelector, rankingFilter, pokeimg_suffix);
				loadGyms(0, $('input#name').filter(':visible').val(), teamSelector, rankingFilter, pokeimg_suffix, false);
			} else {
				window.history.back();
			}
		};

	});
});

function loadTopShaver() {
	$('.topShaverLoader').show();

	$.ajax({
		'async': true,
		'type': "GET",
		'global': false,
		'dataType': 'json',
		'url': "core/process/aru.php",
		'data': {
			'request': '',
			'target': 'arrange_url',
			'method': 'method_target',
			'type' : 'gymshaver_count'
		}
	}).done(function (data) {
		if (data.stats) {
			var statsTable = $('#gymShaverStatsTable');
			statsTable.find('.count-day').html(data.stats.day);
			statsTable.find('.count-week').html(data.stats.week);
			statsTable.find('.count-total').html(data.stats.total);
		}
		var lastCount = 0;
		var place = 0;
		$.each(data.entries, function (idx, entry) {
			if (lastCount != entry.count) {
				place++;
				printTopShaver(place, entry, data.locale);
			} else {
				printTopShaver('', entry, data.locale);
			}
			lastCount = parseInt(entry.count);
		});
		$('.topShaverLoader').hide();
	});
}

function loadGymShaver(page, name, teamSelector, rankingFilter, pokeimg_suffix) {
	$('.gymShaverLoader').show();

	$.ajax({
		'async': true,
		'type': "GET",
		'global': false,
		'dataType': 'json',
		'url': "core/process/aru.php",
		'data': {
			'request': '',
			'target': 'arrange_url',
			'method': 'method_target',
			'type' : 'gymshaver',
			'page' : page,
			'name' : name,
			'team' : teamSelector,
			'ranking' :rankingFilter
		}
	}).done(function (data) {
		var internalIndex = 0;
		$.each(data.entries, function (idx, entry) {
			internalIndex++
			printGymShaver(entry, pokeimg_suffix, data.locale);
		});
		if (internalIndex < 5) {
			$('#loadMoreButtonShaver').hide();
		} else {
			$('#loadMoreButtonShaver').removeClass('hidden');
			$('#loadMoreButtonShaver').show();
		}
		$('.gymShaverLoader').hide();
	});
}

function loadGyms(page, name, teamSelector, rankingFilter, pokeimg_suffix, stayOnPage) {
	$('.gymLoader').show();

	if (stayOnPage) {
		// build a state for this name
		var state = {name: name, page: 'gymhistory'};
		window.history.pushState(state, 'gymhistory', 'gymhistory?name=' + name);
	}
	$.ajax({
		'async': true,
		'type': "GET",
		'global': false,
		'dataType': 'json',
		'url': "core/process/aru.php",
		'data': {
			'request': '',
			'target': 'arrange_url',
			'method': 'method_target',
			'type' : 'gyms',
			'page' : page,
			'name' : name,
			'team' : teamSelector,
			'ranking' :rankingFilter
		}
	}).done(function (data) {
		var internalIndex = 0;
		$.each(data.gyms, function (idx, gym) {
			internalIndex++
			printGym(gym, pokeimg_suffix, data.locale);
		});
		if (internalIndex < 10) {
			$('#loadMoreButton').hide();
		} else {
			$('#loadMoreButton').removeClass('hidden');
			$('#loadMoreButton').show();
		}
		$('.gymLoader').hide();
	});
}

function loadGymHistory(page, gym_id, pokeimg_suffix) {
	$('#gymHistory_'+gym_id).addClass('active').show();
	$('#gymHistory_'+gym_id).find('.gymHistoryLoader').show();

	$.ajax({
		'async': true,
		'type': "GET",
		'global': false,
		'dataType': 'json',
		'url': "core/process/aru.php",
		'data': {
			'request': '',
			'target': 'arrange_url',
			'method': 'method_target',
			'type' : 'gymhistory',
			'page' : page,
			'gym_id' : gym_id
		}
	}).done(function (data) {
		var internalIndex = 0;
		$.each(data.entries, function(idx, entry) {
			internalIndex++
			printGymHistory(gym_id, entry, pokeimg_suffix, data.locale);
		});
		if (internalIndex < 10) {
			$('#gymHistory_'+gym_id).find('.loadMoreButtonHistory').hide();
		} else {
			$('#gymHistory_'+gym_id).find('.loadMoreButtonHistory').removeClass('hidden');
			$('#gymHistory_'+gym_id).find('.loadMoreButtonHistory').data('page', page+1).show();
		}
		$('#gymHistory_'+gym_id).find('.gymHistoryLoader').hide();
	});
}

function printPokemonList(pokemons, pokeimg_suffix, hide_unchanged) {
	var gymPokemon = $('<ul>',{class: 'list-inline'});
	$.each(pokemons, function(idx, pokemon) {
		if (!hide_unchanged || pokemon.class) {
			var list = $('<li>', {class: pokemon.class});
			list.append($('<a>', { class: 'no-link', href : 'pokemon/'+pokemon.pokemon_id }).append($('<img />', { src: 'core/pokemons/'+pokemon.pokemon_id+pokeimg_suffix }).css('height', '2em')));
			list.append($('<br><span class="small">'+pokemon.cp+' CP</span>'));
			list.append($('<br><span style="font-size:70%"><a href="trainer?name='+pokemon.trainer_name+'" class="no-link">'+pokemon.trainer_name+'</a></span>'));
			gymPokemon.append(list);
		}
	});
	return gymPokemon;
}

function printGymHistory(gym_id, entry, pokeimg_suffix, locale) {
	var gymLevel = gymRanks.find(r => r.prestigeMin <= entry.gym_points && r.prestigeMax > entry.gym_points) || { level : 10 };
	var gymHistory = $('<tr>').css('border-bottom', '2px solid '+(entry.team_id=='3'?'#ffbe08':entry.team_id=='2'?'#ff7676':entry.team_id=='1'?'#00aaff':'#ddd'));
	gymHistory.append($('<td>',{text: entry.last_modified}));
	gymHistory.append($('<td>',{text: gymLevel.level, class: 'level'}).prepend($('<img />', {src:'core/img/map_'+(entry.team_id=='1'?'blue':entry.team_id=='2'?'red':entry.team_id=='3'?'yellow':'white')+'.png'})));
	gymHistory.append($('<td>',{text: parseInt(entry.gym_points).toLocaleString('de-DE'), class: entry.class}).append(
		entry.gym_points_diff !== 0 ? $('<span class="small"> ('+(entry.gym_points_diff > 0 ? '+' : '')+entry.gym_points_diff+')</span>') : null
	));
	var gymPokemon = printPokemonList(entry.pokemon, pokeimg_suffix, false);
	gymHistory.append($('<td>').append(gymPokemon));
	$('#gymHistory_'+gym_id).find('tbody').append(gymHistory);
}

function hideGymHistoryTables(gymHistoryTables) {
	gymHistoryTables.removeClass('active').hide();
	gymHistoryTables.find('tbody tr').remove();
	gymHistoryTables.find('.loadMoreButtonHistory').hide();
	gymHistoryTables.find('.gymHistoryLoader').hide();
}

function printGym(gym, pokeimg_suffix, locale) {
	var gymLevel = gymRanks.find(r => r.prestigeMin <= gym.gym_points && r.prestigeMax > gym.gym_points) || { level : 10 };
	var gymsInfos = $('<tr>',{id: 'gymInfos_'+gym.gym_id}).css('cursor', 'pointer').css('border-bottom', '2px solid '+(gym.team_id=='3'?'#ffbe08':gym.team_id=='2'?'#ff7676':gym.team_id=='1'?'#00aaff':'#ddd')).click(function() {
		if (!$('#gymHistory_'+gym.gym_id).hasClass('active')) {
			hideGymHistoryTables($('#gymsContainer').find('.gymhistory'));
			loadGymHistory(0, gym.gym_id, pokeimg_suffix);
		} else {
			hideGymHistoryTables($('#gymHistory_'+gym.gym_id));
		}
	});
	gymsInfos.append($('<td>',{text: gym.last_modified}));
	if (gym.name.length > 50) { gym.name = gym.name.substr(0, 50) + '…'; }
	gymsInfos.append($('<td>',{text: gym.name}));
	gymsInfos.append($('<td>',{text: gymLevel.level, class: 'level'}).prepend($('<img />', {src:'core/img/map_'+(gym.team_id=='1'?'blue':gym.team_id=='2'?'red':gym.team_id=='3'?'yellow':'white')+'.png'})));
	gymsInfos.append($('<td>',{text: parseInt(gym.gym_points).toLocaleString('de-DE')}));
	var gymPokemon = printPokemonList(gym.pokemon, pokeimg_suffix, false);
	gymsInfos.append($('<td>').append(gymPokemon));
	$('#gymsContainer').append(gymsInfos);
	var historyTable = $('<table>',{class: 'table'});
	historyTable.append('<thead><tr><th style="min-width:7em">Time</th><th>Level</th><th>Prestige</th><th>Pokémon</th></tr></thead>');
	historyTable.append('<tbody></tbody>');
	historyTable.append('<tfoot><tr class="loadMore text-center"><td colspan="4"><button class="loadMoreButtonHistory btn btn-default btn-sm hidden">Load more</button></td></tr><tr class="gymHistoryLoader"><td colspan="4"><div class="loader"></div></td></tr></tfoot>');
	historyTable.find('.loadMoreButtonHistory').data('page', 0).click(function() {
		loadGymHistory($(this).data('page'), gym.gym_id, pokeimg_suffix);
	});
	var row = $('<td>',{colspan: 6});
	row.append(historyTable);
	$('#gymsContainer').append($('<tr>', {id: 'gymHistory_'+gym.gym_id, class: 'gymhistory'}).hide().append(row));
}


function printGymShaver(gym, pokeimg_suffix, locale) {
	var gymLevel = gymRanks.find(r => r.prestigeMin <= gym.gym_points_end && r.prestigeMax > gym.gym_points_end) || { level : 10 };
	var gymsInfos = $('<tr>',{id: 'gymInfos_'+gym.gym_id}).css('border-bottom', '2px solid '+(gym.team_id=='3'?'#ffbe08':gym.team_id=='2'?'#ff7676':gym.team_id=='1'?'#00aaff':'#ddd'));
	gymsInfos.append($('<td>').append(gym.last_modified_end + '<br>' + gym.last_modified_start));
	var gymNameShort = gym.name.length > 50 ? gym.name.substr(0, 50) + '…' : gym.name;
	gymsInfos.append($('<td>').append($('<a>', {class: 'no-link', href: 'gymhistory?name='+gym.name, text: gymNameShort})));
	gymsInfos.append($('<td>',{text: gymLevel.level, class: 'level'}).prepend($('<img />', {src:'core/img/map_'+(gym.team_id=='1'?'blue':gym.team_id=='2'?'red':gym.team_id=='3'?'yellow':'white')+'.png'})));
	gymsInfos.append($('<td>',{text: parseInt(gym.gym_points_end).toLocaleString('de-DE'), class: gym.class}).append(
		gym.gym_points_diff !== 0 ? $('<span class="small"> ('+(gym.gym_points_diff > 0 ? '+' : '')+gym.gym_points_diff+')</span>') : null
	));
	var gymPokemon = printPokemonList(gym.pokemon, pokeimg_suffix, true);
	gymsInfos.append($('<td>').append(gymPokemon));
	$('#gymShaverContainer').append(gymsInfos);
}

function printTopShaver(place, entry, locale) {
	var shaver = $('<tr>').css('border-bottom', '2px solid '+(entry.team=='3'?'#ffbe08':entry.team=='2'?'#ff7676':entry.team=='1'?'#00aaff':'#ddd'));
	shaver.append($('<td>',{text: place}));
	shaver.append($('<td>').append($('<a>', {class: 'no-link', href: 'trainer?name='+entry.name, text: entry.name})));
	shaver.append($('<td>').append($('<img />', {src:'core/img/map_'+(entry.team=='1'?'blue':entry.team=='2'?'red':entry.team=='3'?'yellow':'white')+'.png'})));
	shaver.append($('<td>',{text: entry.level, class: 'level'}));
	shaver.append($('<td>',{text: parseInt(entry.count)}));
	$('#topShaverContainer').append(shaver);
}
