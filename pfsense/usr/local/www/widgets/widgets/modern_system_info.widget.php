<?php
/*
 * modern_system_info.widget.php
 * Versão Modernizada
 */

require_once("functions.inc");
require_once("guiconfig.inc");
require_once('notices.inc');
require_once('system.inc');
include_once("includes/functions.inc.php");

// Validação do widgetkey
if ($_POST['widgetkey'] || $_GET['widgetkey']) {
    $rwidgetkey = isset($_POST['widgetkey']) ? $_POST['widgetkey'] : (isset($_GET['widgetkey']) ? $_GET['widgetkey'] : null);
    if (is_valid_widgetkey($rwidgetkey, $user_settings, __FILE__)) {
        $widgetkey = $rwidgetkey;
    } else {
        print gettext("Invalid Widget Key");
        exit;
    }
}

// Itens do sistema com ícones Font Awesome
$sysinfo_items = array(
    'name' => array('text' => gettext('Name'), 'icon' => 'fa-solid fa-signature'),
    'user' => array('text' => gettext('User'), 'icon' => 'fa-solid fa-user'),
    'system' => array('text' => gettext('System'), 'icon' => 'fa-solid fa-server'),
    'bios' => array('text' => gettext('BIOS'), 'icon' => 'fa-solid fa-microchip'),
    'version' => array('text' => gettext('Version'), 'icon' => 'fa-solid fa-code-branch'),
    'cpu_type' => array('text' => gettext('CPU Type'), 'icon' => 'fa-solid fa-microchip'),
    'hwcrypto' => array('text' => gettext('Hardware Crypto'), 'icon' => 'fa-solid fa-lock'),
    'pti' => array('text' => gettext('Kernel PTI'), 'icon' => 'fa-solid fa-shield-halved'),
    'mds' => array('text' => gettext('MDS Mitigation'), 'icon' => 'fa-solid fa-shield-virus'),
    'uptime' => array('text' => gettext('Uptime'), 'icon' => 'fa-solid fa-clock'),
    'current_datetime' => array('text' => gettext('Current Date/Time'), 'icon' => 'fa-solid fa-calendar-day'),
    'dns_servers' => array('text' => gettext('DNS Server(s)'), 'icon' => 'fa-solid fa-globe'),
    'last_config_change' => array('text' => gettext('Last Config Change'), 'icon' => 'fa-solid fa-history'),
    'state_table_size' => array('text' => gettext('State Table Size'), 'icon' => 'fa-solid fa-table-list'),
    'mbuf_usage' => array('text' => gettext('MBUF Usage'), 'icon' => 'fa-solid fa-memory'),
    'temperature' => array('text' => gettext('Temperature'), 'icon' => 'fa-solid fa-temperature-three-quarters'),
    'load_average' => array('text' => gettext('Load Average'), 'icon' => 'fa-solid fa-gauge-high'),
    'cpu_usage' => array('text' => gettext('CPU Usage'), 'icon' => 'fa-solid fa-microchip'),
    'memory_usage' => array('text' => gettext('Memory Usage'), 'icon' => 'fa-solid fa-memory'),
    'swap_usage' => array('text' => gettext('Swap Usage'), 'icon' => 'fa-solid fa-hard-drive')
);

// Variáveis globais
$updtext = sprintf(gettext("Obtaining update status %s"), "<i class='fa-solid fa-rotate fa-spin'></i>");
$state_tt = gettext("Adaptive state handling is enabled, state timeouts are reduced to ");

// Lógica para verificação de atualizações
if ($_REQUEST['getupdatestatus']) {
    require_once("pkg-utils.inc");
    $cache_file = g_get('version_cache_file');

    if (config_path_enabled('system/firmware', 'disablecheck')) {
        exit;
    }

    $system_version = get_system_pkg_version(false, ($_REQUEST['getupdatestatus'] == 1), false, true);

    unset($error);
    if ($system_version === false || !is_array($system_version)) {
        $error = gettext("<i>Unable to check for updates</i>");
    }
    if (isset($system_version['pkg_busy']) || isset($system_version['pkg_version_error'])) {
        $error = gettext("<i>Update system is busy, try again later</i>");
    }
    if (!isset($system_version['version']) || !isset($system_version['installed_version'])) {
        $error = gettext("<i>Error in version information</i>");
    }
    
    if (isset($error)) {
        print($error);
        echo "&nbsp;<a id='updver' href='#' class='fa-solid fa-arrows-rotate'></a>";
        exit;
    }

    // Exibição do status de atualização
    echo "<div class='update-status'>";
    switch ($system_version['pkg_version_compare']) {
        case '<':
            echo "<span class='update-available'><i class='fa-solid fa-circle-exclamation'></i> ".gettext("Version ").
                 "<strong>".$system_version['version']."</strong> ".gettext("is available.").
                 "<a class='btn btn-sm btn-success ml-2' href='/pkg_mgr_install.php?id=firmware'><i class='fa-solid fa-cloud-arrow-down'></i> ".gettext("Update")."</a></span>";
            break;
        case '=':
            echo "<span class='text-success'><i class='fa-solid fa-circle-check'></i> ".gettext("The system is on the latest version.")."</span>";
            break;
        case '>':
            echo "<span class='text-info'><i class='fa-solid fa-circle-info'></i> ".gettext("The system is on a later version than official release.")."</span>";
            break;
        default:
            echo "<span class='text-warning'><i class='fa-solid fa-circle-question'></i> ".gettext("Error comparing installed with latest version available")."</span>";
            break;
    }
    echo "</div>";

    if (file_exists($cache_file)):
        echo "<div class='version-info'>".printf("%s %s", gettext("Version information updated at"),
            date("D M j G:i:s T Y", filemtime($cache_file)))."&nbsp;<a id='updver' href='#' class='fa-solid fa-rotate'></a></div>";
    endif;

    exit;
} elseif ($_POST['widgetkey']) {
    set_customwidgettitle($user_settings);

    $validNames = array_keys($sysinfo_items);

    if (is_array($_POST['show'])) {
        $user_settings['widgets'][$_POST['widgetkey']]['filter'] = implode(',', array_diff($validNames, $_POST['show']));
    } else {
        $user_settings['widgets'][$_POST['widgetkey']]['filter'] = implode(',', $validNames);
    }

    save_widget_settings($_SESSION['Username'], $user_settings["widgets"], gettext("Saved System Information Widget Filter via Dashboard."));
    header("Location: /index.php");
}

$hwcrypto = get_cpu_crypto_support();
$skipsysinfoitems = explode(",", $user_settings['widgets'][$widgetkey]['filter']);
$rows_displayed = false;
// Declare a variável no início do script, com o ponto e vírgula
$temp_use_f = (isset($user_settings['widgets']['thermal_sensors-0']) && !empty($user_settings['widgets']['thermal_sensors-0']['thermal_sensors_widget_show_fahrenheit']));

// Depois use a variável onde precisar
?>

<div class="system-info-widget">
    <div class="table-responsive">
        <table class="table table-hover table-striped table-condensed">
            <tbody>
                <?php
                foreach ($sysinfo_items as $item_key => $item_data) {
                    if (!in_array($item_key, $skipsysinfoitems)) {
                        $rows_displayed = true;
                        echo "<tr>";
                        echo "<th><i class='{$item_data['icon']}'></i> {$item_data['text']}</th>";
                        echo "<td>";
                        
                        // Conteúdo específico para cada item
                        switch ($item_key) {
                            case 'name':
                                echo htmlspecialchars(config_get_path('system/hostname') . "." . config_get_path('system/domain'));
                                break;
                                
                            case 'user':
                                echo htmlspecialchars(get_config_user());
                                break;
                                
                            case 'system':
                                $platform = system_identify_specific_platform();
                                echo isset($platform['descr']) ? $platform['descr'] : gettext('Unknown system');
                                
                                $serial = system_get_serial();
                                if (!empty($serial)) {
                                    echo "<div class='system-detail'><span>".gettext("Serial:")."</span> <strong>{$serial}</strong></div>";
                                }
                                
                                $uniqueid = system_get_uniqueid();
                                if (!empty($uniqueid)) {
                                    echo "<div class='system-detail'><span>".gettext("Netgate Device ID:")."</span> <strong>{$uniqueid}</strong></div>";
                                }
                                break;
                                
                            case 'bios':
                                $_gb = exec('/bin/kenv -q smbios.bios.vendor 2>/dev/null', $biosvendor);
                                $_gb = exec('/bin/kenv -q smbios.bios.version 2>/dev/null', $biosversion);
                                $_gb = exec('/bin/kenv -q smbios.bios.reldate 2>/dev/null', $biosdate);
                                $bootmethod = get_single_sysctl("machdep.bootmethod");
                                
                                if (!empty($biosvendor[0])) {
                                    echo "<div>".gettext("Vendor:")." <strong>{$biosvendor[0]}</strong></div>";
                                }
                                if (!empty($biosversion[0])) {
                                    echo "<div>".gettext("Version:")." <strong>{$biosversion[0]}</strong></div>";
                                }
                                if (!empty($biosdate[0])) {
                                    echo "<div>".gettext("Release Date:")." <strong>".date("D M j Y",strtotime($biosdate[0]))."</strong></div>";
                                }
                                if (!empty($bootmethod)) {
                                    echo "<div>".gettext("Boot Method:")." <strong>".htmlspecialchars($bootmethod)."</strong></div>";
                                }
                                break;
                                
                            case 'version':
                                try {
                                    if (file_exists("/etc/version.buildtime") && filesize("/etc/version.buildtime") > 0) {
                                        $buildtime = file_get_contents("/etc/version.buildtime");
                                    } else {
                                        $buildtime = exec("/usr/local/sbin/pkg-static info -A " . g_get('product_name') . " | /usr/bin/awk '/build_timestamp/ {print \$2;}'");
                                    }
                                    $buildtime = !empty($buildtime) ? date("D M j G:i:s T Y", strtotime($buildtime)) : gettext('Unknown');
                                } catch (Exception $e) {
                                    $buildtime = gettext('Unknown');
                                }
                                echo "<strong>".g_get('product_version_string')."</strong> (".php_uname("m").")<br />";
                                echo gettext('built on')." ".$buildtime;
                                if (!g_get('hideuname')) {
                                    echo "<br /><span title=\"".php_uname("a")."\">".php_uname("s")." ".php_uname("r")."</span>";
                                }
                                if (!config_path_enabled('system/firmware', 'disablecheck')) {
                                    echo "<br /><br /><div id='updatestatus'>{$updtext}</div>";
                                }
                                break;
                                
                            case 'cpu_type':
                                echo htmlspecialchars(get_single_sysctl("hw.model"));
                                echo "<div id=\"cpufreq\">".get_cpufreq()."</div>";
                                $cpucount = get_cpu_count();
                                if ($cpucount > 1) {
                                    echo "<div id=\"cpucount\">".htmlspecialchars($cpucount)." ".gettext('CPUs');
                                    $cpudetail = get_cpu_count(true);
                                    if ($cpudetail != $cpucount) {
                                        echo ": ".htmlspecialchars($cpudetail);
                                    }
                                    echo "</div>";
                                }
                                echo "<div id=\"cpucrypto\">".get_cpu_crypto_string($hwcrypto)."</div>";
                                break;
                                
                            case 'hwcrypto':
                                if ($hwcrypto) {
                                    echo htmlspecialchars(crypto_accel_get_algs($hwcrypto));
                                }
                                break;
                                
                            case 'pti':
                                $pti = get_single_sysctl('vm.pmap.pti');
                                if (strlen($pti) > 0) {
                                    echo ($pti == 0) ? gettext("Disabled") : gettext("Enabled");
                                }
                                break;
                                
                            case 'mds':
                                $mds = get_single_sysctl('hw.mds_disable_state');
                                if (strlen($mds) > 0) {
                                    echo ucwords(htmlspecialchars($mds));
                                }
                                break;
                                
                            case 'uptime':
                                echo "<span id=\"uptime\">".htmlspecialchars(get_uptime())."</span>";
                                break;
                                
                            case 'current_datetime':
                                echo "<div id=\"datetime\">".date("D M j G:i:s T Y")."</div>";
                                break;
                                
                            case 'dns_servers':
                                $dns_servers = get_dns_nameservers(false, true);
                                echo "<ul style=\"margin-bottom:0px\">";
                                foreach ($dns_servers as $dns) {
                                    echo "<li>{$dns}</li>";
                                }
                                echo "</ul>";
                                break;
                                
                            case 'last_config_change':
                                if (config_get_path('revision')) {
                                    echo htmlspecialchars(date("D M j G:i:s T Y", intval(config_get_path('revision/time'))));
                                }
                                break;
                                
                            case 'state_table_size':
                                $pfstatetext = get_pfstate();
                                $pfstateusage = get_pfstate(true);
                                $maxstates = (config_get_path('system/maximumstates', 0) > 0) ? config_get_path('system/maximumstates') : pfsense_default_state_size();
                                $adaptivestart = (config_get_path('system/adaptivestart', 0) > 0) ? config_get_path('system/adaptivestart') : intval($maxstates * 0.6);
                                $adaptiveend = (config_get_path('system/adaptiveend', 0) > 0) ? config_get_path('system/adaptiveend') : intval($maxstates * 1.2);
                                
                                if ($pfstatetext > $adaptivestart) {
                                    $scalingfactor = round(($adaptiveend - $pfstatetext) / ($adaptiveend - $adaptivestart) * 100, 0);
                                    echo '<span id="scaledstates"><br /><a href="#" data-toggle="tooltip" title="" data-placement="right" data-original-title="'.
                                        $state_tt.$scalingfactor.'%">'.gettext("Scaling ").$scalingfactor.'%</a></span>';
                                }
                                echo '<div class="progress">';
                                echo '<div id="statePB" class="progress-bar progress-bar-striped '.($pfstatetext > $adaptivestart ? 'progress-bar-warning' : '').
                                     '" role="progressbar" aria-valuenow="'.$pfstateusage.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$pfstateusage.'%"></div>';
                                echo '</div>';
                                echo '<span id="pfstateusagemeter">'.$pfstateusage.'%</span>&nbsp;';
                                echo '<span id="pfstate">('.htmlspecialchars($pfstatetext).')</span>&nbsp;';
                                echo '<span><a href="diag_dump_states.php">'.gettext("Show states").'</a></span>';
                                break;
                                
                            case 'mbuf_usage':
                                get_mbuf($mbufstext, $mbufusage);
                                echo '<div class="progress">';
                                echo '<div id="mbufPB" class="progress-bar progress-bar-striped" role="progressbar" '.
                                     'aria-valuenow="'.$mbufusage.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$mbufusage.'%"></div>';
                                echo '</div>';
                                echo '<span id="mbufusagemeter">'.$mbufusage.'%</span>&nbsp;';
                                echo '<span id="mbuf">('.htmlspecialchars($mbufstext).')</span>';
                                break;
                                
                            case 'temperature':
                                if ($temp_deg_c = get_temp()) {
                                    $display_temp = $temp_use_f ? $temp_deg_c * 1.8 + 32 : $temp_deg_c;
                                    echo '<div class="progress">';
                                    echo '<div id="tempPB" class="progress-bar progress-bar-striped" role="progressbar" '.
                                         'aria-valuenow="'.$display_temp.'" aria-valuemin="0" aria-valuemax="100" '.
                                         'style="width: '.($temp_use_f ? $temp_deg_c * .212 : $temp_deg_c).'%"></div>';
                                    echo '</div>';
                                    echo '<span id="tempmeter" data-units="'.($temp_use_f ? 'F' : 'C').'">'.$display_temp.'</span>&deg;'.($temp_use_f ? 'F' : 'C');
                                }
                                break;
                                
                            case 'load_average':
                                echo '<div id="load_average" title="'.gettext('Last 1, 5 and 15 minutes').'">'.get_load_average().'</div>';
                                break;
                                
                            case 'cpu_usage':
                                echo '<div class="progress">';
                                echo '<div id="cpuPB" class="progress-bar progress-bar-striped" role="progressbar" '.
                                     'aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>';
                                echo '</div>';
                                echo '<span id="cpumeter">'.sprintf(gettext("Retrieving CPU data %s"), 
                                     '<i class="fa-solid fa-gear fa-spin"></i>').'</span>';
                                break;
                                
                            case 'memory_usage':
                                $memUsage = mem_usage();
                                echo '<div class="progress">';
                                echo '<div id="memUsagePB" class="progress-bar progress-bar-striped" role="progressbar" '.
                                     'aria-valuenow="'.$memUsage.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$memUsage.'%"></div>';
                                echo '</div>';
                                echo '<span id="memusagemeter">'.$memUsage.'</span><span>% of '.
                                     sprintf("%.0f", get_single_sysctl('hw.physmem') / (1024*1024)).' MiB</span>';
                                break;
                                
                            case 'swap_usage':
                                $showswap = true; // Você pode querer definir isso dinamicamente
                                if ($showswap) {
                                    $swapusage = swap_usage();
                                    echo '<div class="progress">';
                                    echo '<div class="progress-bar progress-bar-striped" role="progressbar" '.
                                         'aria-valuenow="'.$swapusage.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$swapusage.'%"></div>';
                                    echo '</div>';
                                    echo '<span>'.$swapusage.'% of '.
                                         sprintf("%.0f", `/usr/sbin/swapinfo -m | /usr/bin/tail -1 | /usr/bin/awk '{ print $2;}'`).' MiB</span>';
                                }
                                break;
                                
                            default:
                                echo gettext("Information not available");
                                break;
                        }
                        
                        echo "</td></tr>";
                    }
                }
                
                if (!$rows_displayed) {
                    echo "<tr><td colspan='2' class='text-center'>".gettext('All System Information items are hidden.')."</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Painel de configuração -->
<div id="<?=$widget_panel_footer_id?>" class="panel-footer collapse">
    <form action="/widgets/widgets/modern_system_info.widget.php" method="post" class="form-horizontal">
        <div class="form-group">
            <div class="col-sm-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><?=gettext("Display Options")?></div>
                    <div class="panel-body">
                        <input type="hidden" name="widgetkey" value="<?=htmlspecialchars($widgetkey); ?>">
                        <div class="row">
                            <?php
                            $i = 0;
                            foreach ($sysinfo_items as $sysinfo_item_key => $sysinfo_item_data):
                                if ($i % 4 == 0) {
                                    echo '</div><div class="row">';
                                }
                                ?>
                                <div class="col-md-3">
                                    <div class="checkbox">
                                        <label>
                                            <input id="show[]" name="show[]" value="<?=$sysinfo_item_key?>" type="checkbox" <?=(!in_array($sysinfo_item_key, $skipsysinfoitems)) ? 'checked':''?>>
                                            <i class="<?=$sysinfo_item_data['icon']?>"></i> <?=$sysinfo_item_data['text']?>
                                        </label>
                                    </div>
                                </div>
                                <?php
                                $i++;
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-12 text-center">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?=gettext('Save')?></button>
                <button id="<?=$widget_showallnone_id?>" type="button" class="btn btn-default"><i class="fa-solid fa-list"></i> <?=gettext('Toggle All')?></button>
            </div>
        </div>
    </form>
</div>

<script type="text/javascript">
// JavaScript modernizado com melhorias de desempenho
events.push(function() {
    // Sistema de atualização AJAX
    const meters_callback = (s) => {
        if (typeof stats === 'function') stats(s);
    };

    const postdata = {
        ajax: "ajax",
        skipitems: <?=json_encode($skipsysinfoitems)?>
    };

    const metersObject = {
        name: "Meters",
        url: "/getstats.php",
        callback: meters_callback,
        parms: postdata,
        freq: 5
    };

    register_ajax(metersObject);

    <?php if (!config_path_enabled('system/firmware', 'disablecheck')): ?>
    // Atualização de versão
    const version_callback = (s) => {
        document.querySelector('[id^=widget-system_information] #updatestatus').innerHTML = s;
        document.getElementById('updver')?.addEventListener('click', updver_ajax);
    };

    const versionObject = {
        name: "Version",
        url: "/widgets/widgets/modern_system_info.widget.php",
        callback: version_callback,
        parms: {...postdata, getupdatestatus: "1"},
        freq: 100
    };

    register_ajax(versionObject);
    <?php endif; ?>

    // Função para atualização manual
    const updver_ajax = () => {
        const updateElement = document.querySelector('[id^=widget-system_information] #updatestatus');
        if (updateElement) {
            updateElement.innerHTML = "<?=$updtext?>";
            
            fetch("/widgets/widgets/modern_system_info.widget.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ajax: "ajax",
                    getupdatestatus: "2",
                    skipitems: JSON.stringify(<?=json_encode($skipsysinfoitems)?>)
                })
            })
            .then(response => response.text())
            .then(data => {
                updateElement.innerHTML = data;
                document.getElementById('updver')?.addEventListener('click', updver_ajax);
            })
            .catch(error => console.error('Error:', error));
        }
    };

    // Event listeners
    set_widget_checkbox_events("#<?=$widget_panel_footer_id?> [id^=show]", "<?=$widget_showallnone_id?>");
    
    // Tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
});
</script>

<style>
.system-info-widget {
    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
}

.system-info-widget .table {
    margin-bottom: 0;
}

.system-info-widget th {
    font-weight: 500;
    color: #555;
}

.system-info-widget td {
    font-weight: 400;
}

.system-detail {
    margin-top: 4px;
    font-size: 0.9em;
    color: #666;
}

.system-detail span {
    color: #888;
}

.update-status {
    margin: 8px 0;
    padding: 8px;
    border-radius: 4px;
    background: #f8f9fa;
}

.update-available {
    color: #d9534f;
    display: flex;
    align-items: center;
    gap: 8px;
}

.version-info {
    font-size: 0.85em;
    color: #6c757d;
    margin-top: 8px;
}

.progress {
    height: 8px;
    margin-bottom: 5px;
}

.progress-bar {
    transition: width 0.5s ease;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .system-info-widget th {
        color: #ccc;
    }
    
    .system-detail {
        color: #aaa;
    }
    
    .system-detail span {
        color: #888;
    }
    
    .update-status {
        background: #2a2a2a;
    }
}
</style>
