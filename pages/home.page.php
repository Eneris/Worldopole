<header id="single-header">
<div class="row">
	<div class="col-md-12 text-center">
		<h1>
			<?= sprintf($locales->HOME_TITLE->$lang, $config->infos->city); ?>
		</h1>
		
	</div>
</div>
</header>

<div class="row area">

	<div class="col-md-3 col-sm-6 col-xs-12 big-data"> <!-- LIVEMON -->
		<a href="pokemon"><img src="core/img/pokeball.png" alt="Tous les pokémon de Lausanne" width=50 class="big-icon"></a>
		<p><a href="pokemon"><big><strong class="total-pkm-js">0</strong> Pokémon</big><br> 
		<?= sprintf($locales->WIDGET_POKEMON_SUB->$lang, $config->infos->city); ?></a></p>
	</div>

	<div class="col-md-3 col-sm-6 col-xs-12 big-data"  style="border-right:1px lightgray solid;border-left:1px lightgray solid;"> <!-- GYMS -->
		<a href="gym"><img src="core/img/rocket.png" alt="Arènes de lausanne" width=50 class="big-icon"></a>
		<p><a href="gym"><big><strong  class="total-gym-js">0</strong> <?= $locales->GYMS->$lang ?></big><br> <?= $locales->WIDGET_GYM_SUB->$lang ?></a></p>

	</div>

	<div class="col-md-3 col-sm-6 col-xs-12 big-data"  style="border-right:1px lightgray solid;"> <!-- POKESTOPS -->
		<a href="pokestops"><img src="core/img/lure-module.png" alt="Pokéstops à Lausanne"  width=50 class="big-icon"></a>
		<p><a href="pokestops"><big><strong class="total-lure-js">0</strong> <?= $locales->LURES->$lang ?></big><br> <?= sprintf($locales->WIDGET_LURES_SUB->$lang, $config->infos->city); ?></a></p>
	</div>

	<div class="col-md-3 col-sm-6 col-xs-12 big-data"> <!-- MAP -->
		<a href="<?= $config->urls->map_url ?>" target="_blank"><img src="core/img/radar-pokemon-lausanne.png" alt="Radar/Map pokemon Lausanne"  width=50 class="big-icon"></a>
		<p><?= $locales->WIDGET_MAP->$lang ?></p>
	</div>

</div>


<div class="row area big-padding"> <!-- LAST 10 POKEMONS -->
	
	<div class="col-md-12 text-center">
		
		<h2 class="text-center sub-title"><?= $locales->RECENT_SPAWNS->$lang ?></h2>
		
		<div class="last-mon-js">
		
				<?php
				$j = 0;
				foreach($recents as $encounter_id => $pokemon){
				$j++;
				$date = new DateTime($pokemon->disappear_time);
				$date->add(new DateInterval('PT2H'));
				$timeleft = $date->format('H:i:s');
				$pid = $pokemon->pokemon_id;
				?>
			<div class="col-md-1 col-xs-4 pokemon-single" pokeid="<?= $pokemon->pokemon_id ?>">
			
				<a href="http://maps.google.fr/maps?f=q&hl=fr&q=<?= $pokemon->latitude ?>,<?= $pokemon->longitude ?>"><img src="core/pokemons/<?= $pokemon->pokemon_id ?>.png" alt="<?= $pokemon->latitude ?>"  class="img-responsive"></a>
				<p class="pkmn-name"><a href="http://maps.google.fr/maps?f=q&hl=fr&q=<?= $pokemon->latitude ?>,<?= $pokemon->longitude ?>"><?=  $pokemons->$pid->name ?><br/><small>disparaît à <?= $timeleft ?></small></a></p>
			
			</div>
			
		<?php }?>
		
		</div>
	

	</div>			
	
</div>

	
<div class="row big padding">
	<h2 class="text-center sub-title"><?= $locales->FIGHT_TITLE->$lang ?></h2>
	
	<?php foreach($home->teams as $team => $total){ ?>
	
	<div class="col-md-3 col-sm-6 col-sm-12 team">

		<div class="row">
			<div class="col-xs-12 col-sm-12">
					<p style="margin-top:0.5em;text-align:center;"><img src="core/img/<?= $team ?>.png" alt="Team <?= $team ?>" class="img-responsive" style="display:inline-block" width=80> <strong class="total-<?= $team ?>-js">0</strong> <?= $locales->GYMS->$lang ?></p>
			</div>
		</div>
		
	</div>
	
	
	<?php }?>
			

</div>

