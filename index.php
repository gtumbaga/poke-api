<?php
    // $base_url = getCurrentUri();
    $base_url = $_SERVER['REQUEST_URI'];
    $routes = array();
    $routes = explode('/', $base_url);

    $myCommand = (isset($routes[2])) ? $routes[2] : "";
    $myID = (isset($routes[3])) ? $routes[3] : "";

    global $formattedOutput;
    $output = array();

    //ROUTES
    switch ($myCommand) {
        case 'pokemon':
            pokemonController($myID);
            break;

        case 'pokelist':
            pokeListController();
            break;


        default:
            defaultController($myID);
            break;
    }


    //CONTROLLERS
    function pokemonController ($myID) {
        global $formattedOutput;
        $output = array();

        $results_pokemon = shell_exec('./q -H -d "," "select * from ./csv/pokemon.csv as t1 left join ./csv/pokemon_species.csv as t2 on t1.id = t2.id where t1.id = ' . $myID . '"');


        $results_types = shell_exec('./q -H -d "," "select * from ./csv/pokemon_types.csv as t1 inner join ./csv/types.csv as t2 on t1.type_id = t2.id where t1.pokemon_id = ' . $myID . '"');
        $results_types = explode(PHP_EOL, trim($results_types));

        $results_baseStats = shell_exec('./q -H -d "," "select * from ./csv/pokemon_stats.csv where pokemon_id = ' . $myID . '"');
        $results_baseStats = explode(PHP_EOL, trim($results_baseStats));

        $pokeInfo = explode(',', $results_pokemon);

        //print "<pre>" . print_r($pokeInfo, true) . "</pre>";
        //print "<pre>" . print_r($results_pokemon, true) . "</pre>";

        foreach ($results_types as $row) {
            $columns = explode(',', $row);
            $pokeTypes[$columns[2]] = $columns[4];
        }
        foreach ($results_baseStats as $row) {
            $columns = explode(',', $row);
            $statName = shell_exec('./q -H -d "," "select identifier from ./csv/stats.csv where id = ' . $columns[1] . '"');
            $pokeStats[trim($statName)] = $columns[2];
        }

        $tmpGrowthRate = shell_exec('./q -H -d "," "select identifier from ./csv/growth_rates.csv where id = ' . $pokeInfo[22] . '"');
        $tmpGrowthRate = trim($tmpGrowthRate);
        $tmpHatchSteps = $pokeInfo[20] * 255;
        $tmpDesc = shell_exec('./q -H -d "," "select flavor_text from ./csv/pokemon_species_flavor_text.csv where species_id = ' . $myID . ' and language_id = 9 and version_id = 26"');
        $tmpDesc = trim(str_replace(array("\"", "\r\n", "\n", "\r"), " ", $tmpDesc));

        //growth chain
        $growthChain = shell_exec('./q -H -d "," "select id, identifier, evolves_from_species_id from ./csv/pokemon_species.csv where evolution_chain_id = ' . $pokeInfo[12] . ' order by evolves_from_species_id"');
        $growthChain = explode(PHP_EOL, trim($growthChain));

        $myCount = 0;

        $chainIDHolder = array();

        foreach ($growthChain as $row) {
            $columns = explode(',', $row);

            //$evolvesFromText = (!isset($columns[2])) ? "<span class='uniform'></span>" : "<span class='uniform'>" . $columns[2] . "</span> >";

            $chainIDHolder[] = array(
                'pid' => $columns[0],
                'name' => $columns[1],
                'evolves_from' => $columns[2]
            );

            //print "<pre>" . print_r($columns, true) . "</pre>";
        }

        // foreach ($growthChain as $row) {
        //     $myCount++;
        //     $columns = explode(',', $row);
        //     //print "<pre>" . print_r($columns, true) . "</pre>";
        //
        //     if (empty($columns[2])) {
        //         $chainIDHolder[1] = array(
        //             'pid' => $columns[0],
        //             'name' => $columns[1]
        //         );
        //     }
        // }
        //
        // $myCount = $myCount - 1;
        //
        // if ($myCount > 0) {
        //     foreach ($growthChain as $row) {
        //         $columns = explode(',', $row);
        //         if ($columns[2] == $chainIDHolder[1]['pid']) {
        //             $chainIDHolder[2] = array(
        //                 'pid' => $columns[0],
        //                 'name' => $columns[1]
        //             );
        //         }
        //     }
        //
        //
        //     $myCount = $myCount - 1;
        //
        //     if ($myCount > 0) {
        //         foreach ($growthChain as $row) {
        //             $columns = explode(',', $row);
        //             if ($columns[2] == $chainIDHolder[2]['pid']) {
        //                 $chainIDHolder[3] = array(
        //                     'pid' => $columns[0],
        //                     'name' => $columns[1]
        //                 );
        //             }
        //         }
        //
        //
        //     }
        //
        // }

        //print "<pre>" . print_r($chainIDHolder, true) . "</pre>";


        //print $tmpDesc;

        $output = array(
            'pokenum' => $pokeInfo[0],
            'pokename' => $pokeInfo[1],
            'species_id' => $pokeInfo[2],
            'height' => $pokeInfo[3],
            'weight' => $pokeInfo[4],
            'base_experience' => $pokeInfo[5],
            'order' => $pokeInfo[6],
            'is_default' => $pokeInfo[7],
            'generation' => $pokeInfo[10],
            'evolution_chain_id' => $pokeInfo[12],
            'evolution_chain' => $chainIDHolder,
            'types' => $pokeTypes,
            'capture_rate' => $pokeInfo[17],
            'base_happiness' => $pokeInfo[18],
            'growth_rate' => $tmpGrowthRate,
            'hatchCycles' => $pokeInfo[20],
            'hatchSteps' => $tmpHatchSteps,
            'baseStats' => $pokeStats,
            'description' => $tmpDesc

        );

        //print "<pre>" . print_r($output, true) . "</pre>";

        $formattedOutput = json_encode($output);


    }

    function pokeListController () {
        global $formattedOutput;
        $output = array();

        $results_pokemon = shell_exec('./q -H -d "," "select * from ./csv/pokemon.csv as t1 left join ./csv/pokemon_species.csv as t2 on t1.id = t2.id where t1.id < 722"');
        $results_pokemon = explode(PHP_EOL, trim($results_pokemon));

        //print "<pre>" . print_r($results_pokemon, true) . "</pre>";

        foreach ($results_pokemon as $row) {
            $columns = explode(',', $row);



            $output[] = array(
                'pokenum' => $columns[0],
                'pokename' => $columns[1],
                'species_id' => $columns[2],
                'height' => $columns[3],
                'weight' => $columns[4],
                'base_experience' => $columns[5],
                'order' => $columns[6],
                'is_default' => $columns[7],
                'generation' => $columns[10],
                'evolution_chain_id' => $columns[12]

            );
        }

        //print "<pre>" . print_r($output, true) . "</pre>";

        $formattedOutput = json_encode($output);
    }

    function defaultController ($myID) {

    }


?>
<?php 
  header('Content-Type: application/json');
  $http_origin = $_SERVER['HTTP_ORIGIN'];
  header("Access-Control-Allow-Origin: $http_origin");
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header('Access-Control-Allow-Methods: POST, GET');  
  print $formattedOutput;
?>
