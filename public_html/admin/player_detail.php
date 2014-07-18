<!DOCTYPE html>
<html>
<head>
<?php
include("include.php");

$db = getDB();
$hwid = sanitize_hwid($_GET["h"]);
$player = verify_hwid($db, $hwid);
?>
	<title><?php echo "$player reports" ?></title>
	<link rel="stylesheet" href="fancybox/jquery.fancybox.css">
	<link rel="stylesheet" href="fancybox/helpers/jquery.fancybox-buttons.css">
	<style type="text/css">
		a, a:visited, a:active, a:hover  {
			color: #FF7200;
			text-decoration: none;
		}
		a:hover {
			text-decoration: underline;
		}
		html {
			color: #D3D3D3;
			font-family: Verdana,Geneva,sans-serif;
			background: #1D1D1C;
			line-height: 1.2;
		}
		body {
			padding: 20px;
			text-align: center;
		}
		table {
			width:800px;
			margin: 0 auto 0 auto;
		}
		td {
			padding: 5px;
			text-align: left;
		}
		tr.even {
			background: #292929;
		}
		th {
			border-bottom: solid 1px #a0a0a0;
			padding: 5px;
		}
		h3 {
			margin: 0;
			color: #FF7200;
		}
		td ul.modlist {
			display: none;
		}
		ul.modlist, ul.modlist li {
			list-style: none outside;
			margin: 0;
			padding: 0;
			text-indent: 0;
			display: block;			
		}
		ul.modlist li {
			color: #D3D3D3;
			font-family: Verdana,Geneva,sans-serif;
			background: #1D1D1C;
			line-height: 1.2;
			text-align: left;
		}
		ul.modlist li.even {
			background: #292929;
		}
		ul.modlist li.notwhite {
			background: #854500;
		}
		ul.modlist li span {
			font-family: monospace;
		}
	</style>
</head>
<body>
<?php
function time_since($since) {
    $chunks = array(
        array(60 * 60 * 24 * 365 , 'year'),
        array(60 * 60 * 24 * 30 , 'month'),
        array(60 * 60 * 24 * 7, 'week'),
        array(60 * 60 * 24 , 'day'),
        array(60 * 60 , 'hour'),
        array(60 , 'minute'),
        array(1 , 'second')
    );

    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }

    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";
    return $print;
}

function mod_sort($a, $b) {
	return strtolower($a['path']) > strtolower($b['path']);
}

function mod_check($modstr) {
	// unpacks mod string into array, checks entries against a whitelist, and sorts the array with suspicious mods to the top
	$mods = array();
	$mods_white = array();
	foreach (explode(";", $modstr) as $modline) {
		list($modpath, $modoff) = explode("|", "$modline|");
		$modpath = trim($modpath);
		if (!$modpath) continue;
		
		$realpath = $modpath;
		
		// replace some common paths to something more readable
		$ndl_progfil = array(
			"C:\\Program Files (x86)\\",
			"C:\\Program Files\\",
			"C:\\PROGRA~2\\",
			"D:\\Program Files (x86)\\",
			"D:\\Program Files\\",
			"E:\\Program Files (x86)\\",
			"E:\\Program Files\\",
			"F:\\Program Files (x86)\\",
			"F:\\Program Files\\",
			"G:\\Program Files (x86)\\",
			"G:\\Program Files\\",
			"D:\\Programy\\"
		);
		$modpath = str_ireplace($ndl_progfil, "[PROGFIL]\\", $modpath);
		$ndl_steam = array(
			"[PROGFIL]\\Steam\\",
			"C:\\Steam\\",
			"D:\\Steam\\",
			"D:\\Games\\Steam\\",
			"E:\\Steam\\",
			"F:\\Steam\\",
			"F:\\Pelit\\Steam\\",
			"C:\\SteamLibrary\\",
			"D:\\SteamLibrary\\",
			"E:\\SteamLibrary\\",
			"E:\\Games\\Steam\\"		
		);
		$modpath = str_ireplace($ndl_steam, "[STEAM]\\", $modpath);
		$ndl_blacklight = array(
			"[STEAM]\\steamapps\\common\\blacklightretribution\\",
			"[PROGFIL]\\Perfect World Entertainment\\Blacklight Retribution\\",
			"[PROGFIL]\\Games\\Blacklight Retribution\\",
			"[PROGFIL]\\Blacklight\\",
			"[PROGFIL]\\Perfect World Entertainment\\Blacklight Retribution OB\\",
			"C:\\Perfect World Entertainment\\Blacklight Retribution\\",
			"D:\\Perfect World Entertainment\\Blacklight Retribution\\",
			"C:\\GAMES\\BLACKLIGHT RETRIBUTION\\",
			"D:\\GAMES\\BLACKLIGHT RETRIBUTION\\",
			"E:\\GAMES\\BLACKLIGHT RETRIBUTION\\",
			"F:\\GAMES\\BLACKLIGHT RETRIBUTION\\",
			"G:\\GAMES\\BLACKLIGHT RETRIBUTION\\Blacklight Retribution OB\\",
			"G:\\GAMES\\BLACKLIGHT RETRIBUTION\\",
			"A:\\Blacklight Retribution\\",
			"C:\\BLACKLIGHT RETRIBUTION\\",
			"D:\\BLACKLIGHT RETRIBUTION\\",
			"D:\\Pliki\\GRY\\Blacklight\\"
		);
		$modpath = str_ireplace($ndl_blacklight, "[BLACKLIGHT]\\", $modpath);
		$ndl_winsys = array(
			"C:\\Windows\\system32\\",
			"C:\\Windows\\syswow64\\"			
		);
		$modpath = str_ireplace($ndl_winsys, "[WINSYS]\\", $modpath);
		$ndl_winapp = array(
			"C:\\Windows\\AppPatch\\"
		);
		$modpath = str_ireplace($ndl_winapp, "[WINAPP]\\", $modpath);
		$ndl_winsxs = array(
			"C:\\Windows\\winsxs\\"
		);		
		$modpath = str_ireplace($ndl_winsxs, "[WINSXS]\\", $modpath);
		$ndl_winnet = array(
			"C:\\Windows\\Microsoft.NET\\Framework\\v4.0.30319\\"
		);		
		$modpath = str_ireplace($ndl_winnet, "[WINNET]\\", $modpath);
		
		$modpath = preg_replace('/C\\:\\\\Users\\\\[^\\\\]+\\\\AppData\\\\/i', "[APPDATA]\\", $modpath);
		$ndl_dxtory = array(
			"[PROGFIL]\\dxtory2.0\\",
			"[PROGFIL]\\Dxtory Software\\Dxtory2.0\\",
			"C:\\dxtory2.0\\",
			"D:\\dxtory2.0\\",
			"E:\\dxtory2.0\\",
			"F:\\dxtory2.0\\"
		);
		$modpath = str_ireplace($ndl_dxtory, "[DXTORY]\\", $modpath);
		$ndl_fraps = array(
			"[PROGFIL]\\fraps\\",
			"C:\\fraps\\",
			"D:\\fraps\\"			
		);
		$modpath = str_ireplace($ndl_fraps, "[FRAPS]\\", $modpath);	
		$ndl_bandi = array(
			"[PROGFIL]\\Bandicam\\",
			"C:\\bandicam\\",
			"D:\\bandicam\\"			
		);
		$modpath = str_ireplace($ndl_bandi, "[BANDICAM]\\", $modpath);	
		
		// check modpath against whitelist
		$whitelist = false;
		$ndl_whitemods = array(
			'[APPDATA]\Local\PunkBuster\BLR\pb\pbag.dll',
			'[PROGFIL]\NVIDIA Corporation\3D Vision\nvSCPAPI.dll',
			'[PROGFIL]\NVIDIA Corporation\3D Vision\nvStereoApiI.dll',
			'[PROGFIL]\NVIDIA Corporation\PhysX\Common\PhysXUpdateLoader.dll',
			'[STEAM]\CSERHelper.dll',
			'[STEAM]\gameoverlayrenderer.dll',
			'[STEAM]\steam.dll',
			'[STEAM]\steamclient.dll',
			'[STEAM]\tier0_s.dll',
			'[STEAM]\vstdlib_s.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\ApexFramework_x86.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\berkelium.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\binkw32.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\BLR.exe',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\cudart32_30_9.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\icudt46.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\nvtt.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\ogg.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\pb\pbcl.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\pb\pbsv.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\PhysXCooking.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\PhysXCore.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\PhysXLoader.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\soundbackends\directsound_win32.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\soundbackends\windowsaudiosession_win32.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\steam_api.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\ts3client_win32.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\vorbis.dll',
			'[BLACKLIGHT]\Blacklight Retribution\Live\Binaries\Win32\vorbisfile.dll',
			'[BLACKLIGHT]\DbgHelp.dll',
			'[WINAPP]\AcGenral.DLL',
			'[WINAPP]\AcLayers.DLL',
			'[WINAPP]\AcXtrnal.DLL',
			'[WINSXS]\x86_microsoft.vc80.crt_1fc8b3b9a1e18e3b_8.0.50727.6910_none_d089c358442de345\MSVCP80.dll',
			'[WINSXS]\x86_microsoft.vc80.crt_1fc8b3b9a1e18e3b_8.0.50727.6910_none_d089c358442de345\MSVCR80.dll',
			'[WINSXS]\x86_microsoft.vc90.crt_1fc8b3b9a1e18e3b_9.0.30729.6871_none_50944e7cbcb706e5\MSVCP90.dll',
			'[WINSXS]\x86_microsoft.vc90.crt_1fc8b3b9a1e18e3b_9.0.30729.6871_none_50944e7cbcb706e5\MSVCR90.dll',
			'[WINSXS]\x86_microsoft.windows.common-controls_6595b64144ccf1df_6.0.9200.16384_none_893961408605e985\COMCTL32.dll',
			'[WINSXS]\x86_microsoft.windows.gdiplus_6595b64144ccf1df_1.1.9200.16518_none_ba1cf6b7e09f1918\gdiplus.dll',
			'[WINSXS]\x86_microsoft.vc80.crt_1fc8b3b9a1e18e3b_8.0.50727.6195_none_d09154e044272b9a\MSVCP80.dll',
			'[WINSXS]\x86_microsoft.vc80.crt_1fc8b3b9a1e18e3b_8.0.50727.6195_none_d09154e044272b9a\MSVCR80.dll',
			'[WINSXS]\x86_microsoft.vc90.crt_1fc8b3b9a1e18e3b_9.0.30729.6161_none_50934f2ebcb7eb57\MSVCP90.dll',
			'[WINSXS]\x86_microsoft.vc90.crt_1fc8b3b9a1e18e3b_9.0.30729.6161_none_50934f2ebcb7eb57\MSVCR90.dll',
			'[WINSXS]\x86_microsoft.windows.common-controls_6595b64144ccf1df_6.0.7601.17514_none_41e6975e2bd6f2b2\COMCTL32.dll',
			'[WINSXS]\x86_microsoft.windows.gdiplus_6595b64144ccf1df_1.1.7601.17825_none_72d273598668a06b\gdiplus.dll',
			'[WINSXS]\x86_microsoft.vc80.crt_1fc8b3b9a1e18e3b_8.0.50727.4940_none_d08cc06a442b34fc\MSVCP80.dll',
			'[WINSXS]\x86_microsoft.vc80.crt_1fc8b3b9a1e18e3b_8.0.50727.4940_none_d08cc06a442b34fc\MSVCR80.dll',
			'[WINSXS]\x86_microsoft.vc90.crt_1fc8b3b9a1e18e3b_9.0.30729.4940_none_50916076bcb9a742\MSVCP90.dll',
			'[WINSXS]\x86_microsoft.vc90.crt_1fc8b3b9a1e18e3b_9.0.30729.4940_none_50916076bcb9a742\MSVCR90.dll',
			'[WINSXS]\x86_microsoft.vc80.crt_1fc8b3b9a1e18e3b_8.0.50727.4927_none_d08a205e442db5b5\MSVCP80.dll',
			'[WINSXS]\x86_microsoft.vc80.crt_1fc8b3b9a1e18e3b_8.0.50727.4927_none_d08a205e442db5b5\MSVCR80.dll',
			'[WINSXS]\x86_microsoft.windows.common-controls_6595b64144ccf1df_6.0.7600.16661_none_420fe3fa2b8113bd\COMCTL32.dll',
			'[WINSXS]\x86_microsoft.windows.gdiplus_6595b64144ccf1df_1.1.7600.16385_none_72fc7cbf861225ca\gdiplus.dll',
			'[WINSXS]\x86_microsoft.windows.gdiplus_6595b64144ccf1df_1.1.7600.17007_none_72f44f3186198a88\gdiplus.dll',
			'[WINSXS]\x86_microsoft.windows.gdiplus_6595b64144ccf1df_1.1.7601.17514_none_72d18a4386696c80\gdiplus.dll',
			'[WINNET]\WPF\wpfgfx_v0400.dll',
			'[WINNET]\WPF\PresentationNative_v0400.dll',
			'[WINNET]\wminet_utils.dll',
			'[WINNET]\nlssorting.dll',
			'[WINNET]\mscoreei.dll',
			'[WINNET]\clrjit.dll',
			'[WINNET]\clr.dll',
			'[WINSYS]\ADVAPI32.dll',
			'[WINSYS]\apphelp.dll',
			'[WINSYS]\AUDIOSES.DLL',
			'[WINSYS]\avrt.dll',
			'[WINSYS]\bcrypt.dll',
			'[WINSYS]\bcryptPrimitives.dll',
			'[WINSYS]\CFGMGR32.dll',
			'[WINSYS]\clbcatq.dll',
			'[WINSYS]\combase.dll',
			'[WINSYS]\CRYPT32.dll',
			'[WINSYS]\CRYPTBASE.dll',
			'[WINSYS]\cryptnet.dll',
			'[WINSYS]\CRYPTSP.dll',
			'[WINSYS]\d3d11.dll',
			'[WINSYS]\d3d9.dll',
			'[WINSYS]\D3DCOMPILER_43.dll',
			'[WINSYS]\d3dx11_43.dll',
			'[WINSYS]\d3dx9_43.dll',
			'[WINSYS]\dbghelp.dll',
			'[WINSYS]\DCIMAN32.dll',
			'[WINSYS]\DDRAW.dll',
			'[WINSYS]\DEVOBJ.dll',
			'[WINSYS]\dhcpcsvc.DLL',
			'[WINSYS]\dhcpcsvc6.DLL',
			'[WINSYS]\DINPUT8.dll',
			'[WINSYS]\DNSAPI.dll',
			'[WINSYS]\dsound.dll',
			'[WINSYS]\dwmapi.dll',
			'[WINSYS]\dxgi.dll',
			'[WINSYS]\DXGIDebug.dll',
			'[WINSYS]\faultrep.dll',
			'[WINSYS]\fwpuclnt.dll',
			'[WINSYS]\gameux.dll',
			'[WINSYS]\GDI32.dll',
			'[WINSYS]\GLU32.dll',
			'[WINSYS]\gpapi.dll',
			'[WINSYS]\HID.DLL',
			'[WINSYS]\iertutil.dll',
			'[WINSYS]\imagehlp.dll',
			'[WINSYS]\IMM32.dll',
			'[WINSYS]\IPHLPAPI.DLL',
			'[WINSYS]\KERNEL32.DLL',
			'[WINSYS]\KERNELBASE.dll',
			'[WINSYS]\ksuser.dll',
			'[WINSYS]\midimap.dll',
			'[WINSYS]\MMDevApi.dll',
			'[WINSYS]\MSACM32.dll',
			'[WINSYS]\msacm32.drv',
			'[WINSYS]\MSASN1.dll',
			'[WINSYS]\MSCTF.dll',
			'[WINSYS]\msctfp.dll',
			'[WINSYS]\MSIMG32.dll',
			'[WINSYS]\msls31.dll',
			'[WINSYS]\msvcrt.dll',
			'[WINSYS]\mswsock.dll',
			'[WINSYS]\msxml6.dll',
			'[WINSYS]\napinsp.dll',
			'[WINSYS]\ncrypt.dll',
			'[WINSYS]\NLAapi.dll',
			'[WINSYS]\NSI.dll',
			'[WINSYS]\NTASN1.dll',
			'[WINSYS]\ntdll.dll',
			'[WINSYS]\ntmarta.dll',
			'[WINSYS]\nvapi.dll',
			'[WINSYS]\nvd3dum.dll',
			'[WINSYS]\ole32.dll',
			'[WINSYS]\OLEACC.dll',
			'[WINSYS]\OLEAUT32.dll',
			'[WINSYS]\OPENGL32.dll',
			'[WINSYS]\pdh.dll',
			'[WINSYS]\pnrpnsp.dll',
			'[WINSYS]\POWRPROF.dll',
			'[WINSYS]\profapi.dll',
			'[WINSYS]\propsys.dll',
			'[WINSYS]\PSAPI.DLL',
			'[WINSYS]\rasadhlp.dll',
			'[WINSYS]\RICHED20.dll',
			'[WINSYS]\RPCRT4.dll',
			'[WINSYS]\rsaenh.dll',
			'[WINSYS]\sechost.dll',
			'[WINSYS]\Secur32.dll',
			'[WINSYS]\SETUPAPI.dll',
			'[WINSYS]\shcore.dll',
			'[WINSYS]\SHELL32.dll',
			'[WINSYS]\SHLWAPI.dll',
			'[WINSYS]\SspiCli.dll',
			'[WINSYS]\USER32.dll',
			'[WINSYS]\USERENV.dll',
			'[WINSYS]\USP10.dll',
			'[WINSYS]\uxtheme.dll',
			'[WINSYS]\VERSION.dll',
			'[WINSYS]\wdmaud.drv',
			'[WINSYS]\wevtapi.dll',
			'[WINSYS]\winhttp.dll',
			'[WINSYS]\WININET.dll',
			'[WINSYS]\WINMM.dll',
			'[WINSYS]\WINMMBASE.dll',
			'[WINSYS]\WINNSI.DLL',
			'[WINSYS]\winrnr.dll',
			'[WINSYS]\WINTRUST.dll',
			'[WINSYS]\WLDAP32.dll',
			'[WINSYS]\Wpc.dll',
			'[WINSYS]\WS2_32.dll',
			'[WINSYS]\wshbth.dll',
			'[WINSYS]\WSOCK32.dll',
			'[WINSYS]\X3DAudio1_7.dll',
			'[WINSYS]\XAPOFX1_5.dll',
			'[WINSYS]\XINPUT1_3.dll',
			'[WINSYS]\XmlLite.dll',
			'[WINSYS]\aticfx32.dll',
			'[WINSYS]\atiu9pag.dll',
			'[WINSYS]\atiumdag.dll',
			'[WINSYS]\atiumdva.dll',
			'[WINSYS]\credssp.dll',
			'[WINSYS]\d3d8thk.dll',
			'[WINSYS]\LPK.dll',
			'[WINSYS]\mssprxy.dll',
			'[WINSYS]\MSVCP100.dll',
			'[WINSYS]\MSVCR100.dll',
			'[WINSYS]\netutils.dll',
			'[WINSYS]\Normaliz.dll',
			'[WINSYS]\RpcRtRemote.dll',
			'[WINSYS]\samcli.dll',
			'[WINSYS]\SAMLIB.dll',
			'[WINSYS]\SensApi.dll',
			'[WINSYS]\urlmon.dll',
			'[WINSYS]\webio.dll',
			'[WINSYS]\wer.dll',
			'[WINSYS]\WMASF.DLL',
			'[WINSYS]\WMVCore.DLL',
			'[WINSYS]\wship6.dll',
			'[WINSYS]\wshtcpip.dll',
			'[WINSYS]\atidxx32.dll', // ati driver
			'[WINSYS]\atiuxpag.dll',
			'[WINSYS]\atiadlxy.dll',
			'[WINSYS]\atigktxx.dll',
			'[WINSYS]\atiglpxx.dll',
			'[WINSYS]\atioglxx.dll',
			'[WINSYS]\nvwgf2um.dll', // nvidia driver
			'[WINSYS]\nvoglv32.DLL',
			'[WINSYS]\nvinit.dll',
			'[WINSYS]\nvumdshim.dll',
			'[WINSYS]\fltlib.dll', // filter lib
			'[WINSYS]\guard32.dll', // comodo firewall
			'[WINSYS]\api-ms-win-downlevel-advapi32-l1-1-0.dll',
			'[WINSYS]\api-ms-win-downlevel-advapi32-l2-1-0.dll',
			'[WINSYS]\api-ms-win-downlevel-normaliz-l1-1-0.dll',
			'[WINSYS]\api-ms-win-downlevel-ole32-l1-1-0.dll',
			'[WINSYS]\api-ms-win-downlevel-shlwapi-l1-1-0.dll',
			'[WINSYS]\api-ms-win-downlevel-user32-l1-1-0.dll',
			'[WINSYS]\api-ms-win-downlevel-version-l1-1-0.dll',
			'[WINSYS]\slc.dll',
			'[WINSYS]\srvcli.dll',
			'[WINSYS]\WINSPOOL.DRV',
			'[WINSYS]\WTSAPI32.dll',
			'[WINSYS]\mpr.dll',
			'[WINSYS]\igd10umd32.dll', // intel graphics driver
			'[WINSYS]\igdumd32.dll',
			'[WINSYS]\igdumdx32.dll',
			'[WINSYS]\ntshrui.dll', // probably malware/spyware
			'[WINSYS]\cscapi.dll', // probably malware/spyware
			'[WINSYS]\sfc.dll',
			'[WINSYS]\sfc_os.DLL',			
			'[WINSYS]\sxs.DLL',
			'[WINSYS]\shfolder.dll',
			'[WINSYS]\NTDSAPI.dll',
			'[WINSYS]\MSVCR100_CLR0400.dll',
			'[WINSYS]\mscoree.dll',
			'[WINSYS]\SHUNIMPL.DLL',
			'[WINSYS]\SortWindows6Compat.dll',
			'[WINSYS]\d3d10_1.dll',
			'[WINSYS]\d3d10_1core.dll',
			'[WINSYS]\d3d10.dll',
			'[WINSYS]\d3d10core.dll',
			'[WINSYS]\d3dx10_43.dll',
			'[WINSYS]\WindowsCodecs.dll',
			'[WINSYS]\wbemcomn.dll',
			'[WINSYS]\wbem\wmiutils.dll',
			'[WINSYS]\wbem\wbemsvc.dll',
			'[WINSYS]\wbem\wbemprox.dll',
			'[WINSYS]\wbem\fastprox.dll',
			'[WINSYS]\SortWindows61.dll',
			'[WINSYS]\Cabinet.dll',
			'[WINSYS]\DEVRTL.dll',
			'[WINSYS]\NETAPI32.dll',
			'[WINSYS]\wkscli.dll',
			'[WINSYS]\CRTDLL.dll',
			'[WINSYS]\wbemcomn2.dll',
			'[WINSYS]\OLEPRO32.DLL',
			'[WINSYS]\comdlg32.dll',
			'[WINSYS]\msvfw32.dll',
			'[WINSYS]\LightFX.dll', // alienware?
			'[PROGFIL]\Common Files\Microsoft Shared\Windows Live\WLIDNSP.DLL',
			'[PROGFIL]\Overwolf\OWClient.dll', // overwolf overlay
			'[PROGFIL]\Overwolf\OWExplorer-10616.dll',
			'[PROGFIL]\Overwolf\OWLog.dll',
			'[PROGFIL]\Bonjour\mdnsNSP.dll',
			'[PROGFIL]\Raptr\ltc_help32-68721.dll', // raptr overlay
			'[PROGFIL]\Raptr\ltc_game32-68721.dll',
			'[PROGFIL]\MSI Afterburner\Bundle\OSDServer\RTSSHooks.dll',
			'[PROGFIL]\EVGA Precision X\Bundle\OSDServer\RTSSHooks.dll',
			'[PROGFIL]\OBS\64bit\plugins\GraphicsCapture\GraphicsCaptureHook.dll', // open broadcaster software
			'[PROGFIL]\Common Files\microsoft shared\ink\tiptsf.dll', // MS tablet thing
			'[PROGFIL]\Common Files\Spigot\Search Settings\wth160.dll', // widgi toolbar
			'[DXTORY]\DxtoryCore.dll',
			'[DXTORY]\DxtoryHK.dll',
			'[DXTORY]\DxtoryMM.dll',
			'[FRAPS]\fraps32.dll',
			'[BANDICAM]\bdcam.dll',
			'[PROGFIL]\NVIDIA Corporation\CoProcManager\_etoured.dll', // nvidia but what? not sure about these
			'[PROGFIL]\NVIDIA Corporation\CoProcManager\nvd3d9wrap.dll',
			'[PROGFIL]\NVIDIA Corporation\CoProcManager\nvdxgiwrap.dll',
			'[PROGFIL]\Mumble\mumble_ol.dll',
			'[PROGFIL]\AVAST Software\Avast\snxhk.dll',
			'[PROGFIL]\WIDCOMM\Bluetooth Software\SysWOW64\BtMmHook.dll',
			'[PROGFIL]\Norton Internet Security\Engine\18.7.2.3\ccL100U.dll',
			'[PROGFIL]\Norton Internet Security\Engine\17.9.0.12\ccL90U.dll',
			'[PROGFIL]\TeamSpeak 3 Client\plugins\ts3overlay\ts3overlay_hook_win32.dll',
			'[APPDATA]\Local\TeamSpeak 3 Client\plugins\ts3overlay\ts3overlay_hook_win32.dll',
			'[PROGFIL]\Logitech\SetPoint\x86\lgscroll.dll',
			'[PROGFIL]\Norton 360\Engine\5.2.2.3\ccL100U.dll',
			'[PROGFIL]\Citrix\ICA Client\ShellHook.dll',
			'[PROGFIL]\Citrix\ICACLI~1\RSHook.dll',
			'[PROGFIL]\TeamViewer\Version8\tv_w32.dll',
			'C:\mumble_ol.dll',
			'D:\mumble_ol.dll',
			'E:\mumble_ol.dll'			
		);
		$testval = str_ireplace($ndl_whitemods, "", $modpath);
		if ($testval == "") $whitelist = true;
		
		if ($whitelist) {
			$mods_white[] = array(
				'path' => $modpath,
				'realpath' => $realpath,
				'offset' => $modoff,
				'white' => true
			);
		}
		else {
			$mods[] = array(
				'path' => $modpath,
				'realpath' => $realpath,
				'offset' => $modoff,
				'white' => false
			);
		}
	}
	usort($mods, "mod_sort");
	usort($mods_white, "mod_sort");	
	return array_merge($mods, $mods_white);
}

if (isset($_GET["c"])) {
	$perpage = filter_var($_GET["c"], FILTER_SANITIZE_NUMBER_INT);
}
else {
	$perpage = 50;
}
if (!$db) {
	echo "<p>ERROR: $err</p>";
}
if (!$player) {
	echo "<p>invalid HWID</p>";
}
else {
	$curtime = time();
	echo "<h3><a href='players.php'>&laquo;</a> reports for $player</h3>\n";
	if ($perpage == 50) {
		echo "showing 50 most recent; <a href='?h=$hwid&c=10000'>show all</a>\n";
	}
	else {
		echo "showing all; <a href='?h=$hwid'>show 50 most recent</a>\n";
	}
	echo "<table cellspacing='0' cellpadding='0'><thead><tr><th>report time</th><th>screenshot</th><th>mod list</th></tr></thead><tbody>\n";
	$qr = $db->query("SELECT r.id,r.utimestamp,r.imgsize,r.moddata FROM reports AS r WHERE r.hwid='$hwid' ORDER BY r.utimestamp DESC LIMIT 0,$perpage");
	$rownum = 0;
	$rowdata = array();
	while ($row = $qr->fetchArray(SQLITE3_NUM)) {
		$rowdata[] = array($row[0],$row[1],$row[2],$row[3]);
	}
	$qr->finalize();	
	$db->close();
	foreach ($rowdata as $row) {
		$timestr = date("Y-m-d H:i:s T", $row[1]) . " (" . time_since($curtime - $row[1]) . " ago)";
		$imgsize = "<a href='data/$hwid/$row[1].jpg' rel='reportshot' class='fancybox' title='$timestr'>" . round($row[2] / 1024, 1) . " kB</a>";
		$mods = mod_check($row[3]);
		$modstr = "<a href='#modlist-$row[1]' rel='modlist' class='fancybox' title='$timestr'>" . count($mods) . " items</a><ul id='modlist-$row[1]' class='modlist'>";
		$modnum = 0;
		foreach ($mods as $mod) {
			$modstr .= "<li title='$mod[realpath]' class='" . (($modnum++ % 2) ? "even" : "odd") . ($mod['white'] ? " white" : " notwhite") . "'><span>$mod[offset]</span> $mod[path]</li>";
		}
		$modstr .= "</ul>";
		echo "<tr id='rowid-$row[0]' class='" . (($rownum++ % 2) ? "even" : "odd") . "'><td>$timestr</td><td>$imgsize</td><td>$modstr</td></tr>\n";
	}
	echo "</tbody></table>\n";
}
?>

	<script src="fancybox/jquery-1.9.0.min.js"></script>
	<script src="fancybox/jquery.fancybox.pack.js"></script>
	<script src="fancybox/helpers/jquery.fancybox-buttons.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$(".fancybox").fancybox({
				prevEffect: 'none',
				nextEffect: 'none',
				closeBtn: false,
				loop: false,
				arrows: false,
				helpers: {
					title: { type : 'inside' },
					buttons: {}
				}
			});
		});
	</script>
</body>
</html>