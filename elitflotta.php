<?php

/**
 * PHP (curl)
 * 
 * @version 2019.07.29.
 * @package elitflotta_export
 * @author Soós András
 */

class elitflotta_export
{
    protected $session_id;
    protected $flotta_datas;
    protected $data_page;
    protected $export_datas;
    
    public function start()
    {
        $return = $this->html_head();
        $usr = !empty($_POST['usr']) ? $_POST['usr'] : '';
        $psw = !empty($_POST['psw']) ? $_POST['psw'] : '';
        $is_export = !empty($_POST['is_export']) ? $_POST['is_export'] : '';
        $this->session_id = !empty($_POST['session_id']) ? $_POST['session_id'] : '';
        
        if (empty($usr) && empty($psw) && empty($is_export))
        {
            $return.= '<div id="login">';
                $return.= '<form action="elitflotta.php" method="post" class="login_form">';
                    $return.= '<div class="header_text">'
                            . '<span>AZONOSÍTÁS<br></span>'
                            . '<br>'
                            . 'Kérem adja meg az iroda.elitflotta.hu bejelentkezési adatait<br>'
                            . 'az exportálandó tételek kiolvasásához<br></div>';
                    $return.= '<input id="usr" type="text" name="usr">';
                    $return.= '<input id="psw" type="password" name="psw">';
                    $return.= '<input id="request_btn" type="submit" value="Adatok lekérése">';
                    $return.= '<div class="bottom_info">Az oldal sem bejelentkezési, sem más adatokat nem kezel és nem tárol,<br>'
                            . 'azokat közvetlenül az iroda.elitflotta.hu részére küldi, illetve onnan fogadja a feldogozáshoz!</div>';
                $return.= '</form>';
            $return.= '</div>';
        }
        else if (!empty($is_export))
        {
            if(count($_POST)>1)
            {
                foreach ($_POST as $k => $v)
                {
                    if ($k == 'is_export' && $k == 'session_id')
                    {
                        continue;
                    }
                    else
                    {
                        $postdata = array(
                            'sz' => ($v==2) ? 'c' : 'm',
                            'q' => $k,
                        );
                        $this->browser_emulation('data_pages',$postdata);
                        $this->collecting_datas($v);
                    }
                }
                $this->write_xlsx();
            }
        }
        else
        {
            $postdata = array(
                'nev' => $usr,
                'pass' => $psw,
                'submit' => 'belépés',
            );
            
            $this->browser_emulation('login',$postdata);
            $datas = $this->get_rows();
            $return.= $this->show_checkboxes($datas);
        }
        
        return $return;
    }
    
    public function comment_processing($comment)
    {
        $comment = strtolower($comment);
        $return = array(
            'counter' => 0,
            'phones' => array(),
            'flottas' => array(),
            'mobilenets' => array(),
            'devices' => array(),
        );
        
        $phone_numbers = array();
        preg_match_all('/[0-9]{11}/',$comment,$phone_numbers);
        if (!empty($phone_numbers[0]))
        {
            foreach($phone_numbers[0] as $v)
            {
                $return['phones'][] = (substr($v,0,2) == '36') ? substr($v,2) : $v;
            }
            $return['counter'] = count($return['phones']);
        }
            
        $flotta_preg = array();
        preg_match_all("/flotta(.*)<br>/U", $comment, $flotta_preg);
        if (!empty($flotta_preg[0]))
        {
            $c = count($flotta_preg[0]);
            for ($i=0;$i<$c;$i++)
            {
                if (is_numeric(substr($flotta_preg[0][$i],strrpos($flotta_preg[0][$i],'flotta')+6,1)))
                {
                    $return['flottas'][] = 'Flotta ' . substr($flotta_preg[0][$i],strrpos($flotta_preg[0][$i],'flotta')+6,1);
                }
                else if (is_numeric(substr($flotta_preg[0][$i],strrpos($flotta_preg[0][$i],'flotta')+6,2)))
                {
                    $return['flottas'][] = 'Flotta ' . substr($flotta_preg[0][$i],strrpos($flotta_preg[0][$i],'flotta')+6,2);
                }
            }
            $return['counter'] = (count($return['phones'])>$return['counter']) ? count($return['phones']) : $return['counter'];
        }
            
        $mobilenet_preg = array();
        preg_match_all("/flotta(.*)<br>/U", $comment, $mobilenet_preg);
        if (!empty($mobilenet_preg[0]))
        {
            $c = count($mobilenet_preg[0]);
            for ($i=0;$i<$c;$i++)
            {
                if (strpos($mobilenet_preg[0][$i],'gb') || strpos($mobilenet_preg[0][$i],'mb'))
                {
                    $unit = !empty(strpos($mobilenet_preg[0][$i],'gb')) ? substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],'gb'),2) : substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],'mb'),2);
                    $num = 0;
                    if (is_numeric(substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-1,1)))
                    {
                        $num+= substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-1,1);
                        if (is_numeric(substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-2,1)))
                        {
                            $num+= substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-2,1)*10;
                            if ($unit == 'mb' && is_numeric(substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-3,1)))
                            {
                                $num+= substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-3,1)*100;
                            }
                        }
                    }
                    else
                    {
                        if (is_numeric(substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-2,1)))
                        {
                            $num+= substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit,$i)-2,1);
                            if (is_numeric(substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit,$i)-3,1)))
                            {
                                $num+= substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit,$i)-3,1)*10;
                                if ($unit == 'mb' && is_numeric(substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-4,1)))
                                {
                                    $num+= substr($mobilenet_preg[0][$i],strpos($mobilenet_preg[0][$i],$unit)-4,1)*100;
                                }
                            }
                        }
                    }
                }
                $return['mobilenets'][] = ($num>0) ? $num . ' ' . strtoupper($unit) : '';
            }
            $return['counter'] = (count($return['mobilenets'])>$return['counter']) ? count($return['mobilenets']) : $return['counter'];
        }
        
        if (strpos($comment, 'készülék'))
        {
            $devices_preg = array();
            preg_match_all("/készülék:(.*)<br>/U", $comment, $devices_preg);
            if(!empty($devices_preg[1]))
            {
                $c = count($devices_preg[0]);
                for ($i=0;$i<$c;$i++)
                {
                    $return['devices'][] = str_replace('<br>','',trim($devices_preg[0][$i]));
                }
            }
            $return['counter'] = (count($return['devices'])>$return['counter']) ? count($return['devices']) : $return['counter'];
        }
    
        return $return;
    }

    public function export_datas_processing_before()
    {
        if (!empty($this->export_datas['Magánszemélyek']))
        {
            $pu_header_ids = array_flip($this->export_datas['Magánszemélyek']['header']);
            $private_users = array(
                'header' => array(
                    0 => 'Telefonszám',
                    1 => 'Ideiglenes telefonszám',
                    2 => 'Tarifacsomag',
                    3 => 'Mobilnet csomag',
                    4 => 'Szerződő teljes neve (hivatalos okmányban szereplő)',
                    5 => 'Szerződő születési neve',
                    6 => 'Szerződő állandó lakcíme',
                    7 => 'Számlafizető neve',
                    8 => 'Számlaküldési cím',
                    9 => 'Emailcím (elektronikus számlázási igény esetén)',
                    10 => 'Szerződő személyi ig.v. útlevél száma',
                    11 => 'Szerződő születési dátuma',
                    12 => 'Szerződő születési helye',
                    13 => 'Szerződő édesanyja neve',
                    14 => 'Új készülék igénylése esetén annak pontos típusa és színe',
                    15 => 'Részletfizetés',
                    16 => 'Szerződő telefonos elérhetősége',
                    17 => 'Salesmann',
                    18 => 'Elkészült',
                    19 => 'Fizetendő',
                    20 => 'Igény beérkezése Elitflottától',
                    21 => 'Státusz',
                    22 => 'Státusz időpontja',
                    23 => 'Visszahívás',
                    24 => 'Megjegyzés',
                    25 => 'Szerződés, készülék átadás módja',
                    26 => 'Szerződés visszaérkezett',
                ),
                'content' => array(),
            );
            
            $comment = array();
            foreach ($this->export_datas['Magánszemélyek']['content'] as $k => $v)
            {
                $comment = $this->comment_processing($v[$pu_header_ids['Megjegyzés']]);
                $c = !empty($comment['counter']) ? $comment['counter'] : 1;
                for($i=0;$i<$c;$i++)
                {
                    $private_users['content'][] = array(
                        0 => !empty($comment['phones'][$i]) ? $comment['phones'][$i] : '',
                        1 => '',
                        2 => !empty($comment['flottas'][$i]) ? $comment['flottas'][$i] : '',
                        3 => !empty($comment['mobilenets'][$i]) ? $comment['mobilenets'][$i] : '',
                        4 => $v[$pu_header_ids['Név']],
                        5 => $v[$pu_header_ids['Születési név']],
                        6 => $v[$pu_header_ids['Állandó lakcím:']],
                        7 => $v[$pu_header_ids['Név']],
                        8 => $v[$pu_header_ids['Számlázási cím:']],
                        9 => $v[$pu_header_ids['Email']],
                        10 => $v[$pu_header_ids['Sz. ig. szám']],
                        11 => str_replace('-','.',$v[$pu_header_ids['Születési idő']]) . '.',
                        12 => $v[$pu_header_ids['Születési hely']],
                        13 => $v[$pu_header_ids['Anyja neve']],
                        14 => !empty($comment['devices'][$i]) ? $comment['devices'][$i] : '',
                        15 => '',
                        16 => $v[$pu_header_ids['Telefonszám']],
                        17 => '',
                        18 => '',
                        19 => '',
                        20 => date("Y.m.d"),
                        21 => '',
                        22 => '',
                        23 => '',
                        24 => '',
                        25 => $v[$pu_header_ids['Kiszállítási cím:']],
                        26 => '',
                    );
                }
            }
            $this->export_datas['Magánszemélyek'] = $private_users;
        }
        
        if (!empty($this->export_datas['Céges']))
        {
            $co_header_ids = array_flip($this->export_datas['Céges']['header']);
            $companies = array(
                'header' => array(
                    0 => 'Telefonszám',
                    1 => 'Ideiglenes hívószám',
                    2 => 'Tarifacsomag',
                    3 => 'Mobilinternet',
                    4 => 'Szerződő cég neve',
                    5 => 'Cégjegyzékszám',
                    6 => 'Adószám',
                    7 => 'Bankszámlaszám',
                    8 => 'Székhely',
                    9 => 'Aláíró személy',
                    10 => 'Aláíró személy sz. ig. száma',
                    11 => 'Aláíró születési dátuma',
                    12 => 'Aláíró születési helye',
                    13 => 'Aláíró anyja leánykori neve',
                    14 => 'Aláíró állandó címe',
                    15 => 'Számlafizető neve',
                    16 => 'Számlaküldési cím',
                    17 => 'Számlaküldési emailcím',
                    18 => 'Kapcsolattartó elérhetősége',
                    19 => 'Készülékigény',
                    20 => 'Salesmann',
                    21 => 'Elkészült',
                    22 => 'Fizetendő',
                    23 => 'Igény beérkezése Elitflottától',
                    24 => 'Státusz',
                    25 => 'státusz időpontja',
                    26 => 'Visszahívás',
                    27 => 'Megjegyzés',
                    28 => 'Átadás módja',
                    29 => 'Szerződés visszaérkezett',
                ),
                'content' => array(),
            );
            
            $comment = array();
            foreach ($this->export_datas['Céges']['content'] as $k => $v)
            {
                $comment = $this->comment_processing($v[$co_header_ids['Megjegyzés:']]);
                $c = !empty($comment['counter']) ? $comment['counter'] : 1;
                for($i=0;$i<$c;$i++)
                {
                    $companies['content'][] = array(
                        0 => !empty($comment['phones'][$i]) ? $comment['phones'][$i] : '',
                        1 => '',
                        2 => !empty($comment['flottas'][$i]) ? $comment['flottas'][$i] : '',
                        3 => !empty($comment['mobilenets'][$i]) ? $comment['mobilenets'][$i] : '',
                        4 => $v[$co_header_ids['Cégnév:']],
                        5 => $v[$co_header_ids['Cégjegyzékszám:']],
                        6 => $v[$co_header_ids['Adószám:']],
                        7 => $v[$co_header_ids['Bankszámla szám:']],
                        8 => $v[$co_header_ids['Székhely:']],
                        9 => $v[$co_header_ids['Aláíró neve:']],
                        10 => $v[$co_header_ids['Aláíró szigsz:']],
                        11 => str_replace('-','.',$v[$co_header_ids['Aláíró szül.idő:']]) . '.',
                        12 => $v[$co_header_ids['Aláíró szül.hely:']],
                        13 => $v[$co_header_ids['Aláíró anyja neve:']],
                        14 => $v[$co_header_ids['Aláíró állandó lakcíme:']],
                        15 => $v[$co_header_ids['Cégnév:']],
                        16 => $v[$co_header_ids['Számlázási cím:']],
                        17 => $v[$co_header_ids['Email cím:']],
                        18 => $v[$co_header_ids['Kapcsolattartó telefonszám:']],
                        19 => !empty($comment['devices'][$i]) ? $comment['devices'][$i] : '',
                        20 => '',
                        21 => '',
                        22 => '',
                        23 => date("Y.m.d"),
                        24 => '',
                        25 => '',
                        26 => '',
                        27 => '',
                        28 => $v[$co_header_ids['Kiszállítási cím:']],
                        29 => '',
                    );
                }
            }
            $this->export_datas['Céges'] = $companies;
        }
    }
    
    public function write_xlsx()
    {
        $this->export_datas_processing_before();
        
        include_once("phpxlsxwriter/xlsxwriter.class.php");
        $writer = new XLSXWriter();
        if (!empty($this->export_datas['Magánszemélyek']))
        {
            $writer->writeSheetRow('Magánszemélyek', $this->export_datas['Magánszemélyek']['header']);
            foreach ($this->export_datas['Magánszemélyek']['content'] as $row)
            {
                $writer->writeSheetRow('Magánszemélyek', $row);
            }
        }
        
        if (!empty($this->export_datas['Céges']))
        {
            $writer->writeSheetRow('Céges', $this->export_datas['Céges']['header']);
            foreach ($this->export_datas['Céges']['content'] as $row)
            {
                $writer->writeSheetRow('Céges', $row);
            }
        }
        $file = 'exported_elitflotta.xlsx';
        $writer->writeToFile($file);
        $this->downloadablizer($file);
    }
    
    public function downloadablizer($file)
    {
        if (file_exists($file))
        {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink($file);
            exit;
        }
    }
    
    public function collecting_datas($type)
    {
        $export_page = ($type==1) ? 'Magánszemélyek' : (($type==2) ? 'Céges' : '');
        if (!empty($export_page))
        {
            if (empty($this->export_datas[$export_page]['header']))
            {
                $keys_preg = array();
                preg_match_all("/<TD(.*)<\/TD>/U", $this->data_page, $keys_preg);
                $kc = count($keys_preg[1]);
            }
            else
            {
                $kc = count($this->export_datas[$export_page]['header']);
            }
            
            $values_preg = array();
            preg_match_all("/<td(.*)<\/td>/U", $this->data_page, $values_preg);
            
            $c = $kc - ($kc - count($values_preg[1]));
            $header_datas = array();
            $content_datas = array();
            for($i=0;$i<$c;$i++)
            {
                if (empty($this->export_datas[$export_page]['header']))
                {
                    $header_datas[] = substr(ltrim(rtrim($keys_preg[1][$i])),1);
                }
                $content_datas[] = substr(ltrim(rtrim($values_preg[1][$i])),1);
            }
            
            if (!empty($header_datas))
            {
                $this->export_datas[$export_page]['header'] = $header_datas;
            }
            
            if (!empty($content_datas))
            {
                $this->export_datas[$export_page]['content'][] = $content_datas;
            }
        }
    }
    
    public function show_checkboxes($datas)
    {
        $return = '';
        
        $return.= '<div id="selection">';
            $return.= '<div class="list_box">';
                $return.= '<div class="justaminute">Kérem várjon...</div>';
                
                $return.= '<form action="/judit/elitflotta.php" method="post" class="checkbox_list_form">';
                    $return.= '<div class="page_title">Export lista összeállítása</div>';    
                    
                    $return.= '<div class="checkbox_title">' . 'Magánszemélyek kiválasztása:' . '</div>';
                    $return.= '<div class="input_rows private_users">';
                        if (!empty($datas['private_users']))
                        {
                            foreach ($datas['private_users'] as $k => $v)
                            {
                                $return.= '<label><input type="checkbox" name="' . $k . '" value="1"><span>' . $v['name'] . '</span><span> ' . $v['phone'] . ' </span><span>' . $v['prepay'] . ' </span><span>' . $v['status'] . '</span></label>';
                            }
                        }
                        else
                        {
                            $return.= '<div class="none_items">' . 'Nincsenek magánszemélyekhez kapcsolódó adatok!' . '</div>';
                        }
                    $return.= '</div>';

                    $return.= '<div class="checkbox_title">' . 'Cégek kiválasztása:' . '</div>';
                    $return.= '<div class="input_rows companies">';
                        if (!empty($datas['private_users']))
                        {
                            foreach ($datas['companies'] as $k => $v)
                            {
                                $return.= '<label><input type="checkbox" name="' . $k . '" value="2"><span>' . $v['name'] . '</span><span> ' . $v['phone'] . ' </span><span>' . $v['prepay'] . ' </span><span>' . $v['status'] . '</span></label>';
                            }
                        }
                        else
                        {
                            $return.= '<div class="none_items">' . 'Nincsenek cégekhez kapcsolódó adatok!' . '</div>';
                        }
                    $return.= '</div>';

                    $return.= '<input type="hidden" name="is_export" value="1">';
                    $return.= '<input type="hidden" name="session_id" value="' . $this->session_id . '">';
                    $return.= '<input id="collected_ids_btn" type="submit" value="EXPORT">';
                    $return.= '<div class="bottom_text">F5 gomb lenyomására a lista frissül</div>';
                $return.= '</form>';
            
            $return.= '</div>';
        $return.= '</div>';
        return $return;
    }
    
    public function get_rows()
    {
        $return = array();
        
        $private_users_pregs = array();
        preg_match_all("/<td(.*)<\/td>/U", $this->flotta_datas['private_users'], $private_users_pregs);
        
        $companies_pregs = array();
        preg_match_all("/<td(.*)<\/td>/U", $this->flotta_datas['companies'], $companies_pregs);
        
        $return['private_users'] = $this->get_row_datas($private_users_pregs[0]);
        $return['companies'] = $this->get_row_datas($companies_pregs[0],1);
        
        return $return;
    }
    
    public function get_row_datas($datas,$type=0)
    {
        $return = array();
        
        $modulus = 12-$type;
        $c = count($datas);
        for($i=0;$i<$c;$i++)
        {
            if ($i==0 || $i%$modulus==0)
            {
                $id_preg = array();
                preg_match_all("/q=(.*)'/U", $datas[$i], $id_preg);
                $id = $id_preg[1][0];
                
                $name_preg = array();
                preg_match_all("/;\">(.*)<\/a>/U", $datas[$i], $name_preg);
                $name = $name_preg[1][0];
            }
            else if ($i==1 || $i%$modulus==1)
            {
                $phone_preg = array();
                preg_match_all("/;\">(.*)<\/a>/U", $datas[$i], $phone_preg);
                $phone = $phone_preg[1][0];
            }
            else if ($i==4-$type || $i%$modulus==4-$type)
            {
                $prepay = $datas[$i];
            }
            else if ($i==5-$type || $i%$modulus==5-$type)
            {
                $status = $datas[$i];
            }
            
            if (!empty($name) && !empty($phone) && !empty($status))
            {
                $return[$id] = array(
                    'name' => $name,
                    'phone' => $phone,
                    'prepay' => $prepay,
                    'status' => $status,
                );
            }
        }
        
        return $return;
    }
    
    public function browser_emulation($cycle,$postdata = array())
    {
        $ch = curl_init();

        switch ($cycle)
        {
            case 'login':
                curl_setopt_array($ch, array(
                    CURLOPT_URL => 'http://iroda.elitflotta.hu',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postdata,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HEADER => true,
                    CURLOPT_AUTOREFERER => true,
                ));
                ${$cycle} = curl_exec($ch);
                $sespregmatch = array();
                preg_match_all("/PHPSESSID=(.*);/U", ${$cycle}, $sespregmatch);
                $this->session_id = $sespregmatch[1][0];
                $this->browser_emulation('private_users');
            break;
        
            case 'private_users':
                curl_setopt_array($ch, array(
                    CURLOPT_URL => 'http://iroda.elitflotta.hu/lists.php?q=m',
                    CURLOPT_RETURNTRANSFER => true,                            
                    CURLOPT_HTTPHEADER=> array("Cookie: PHPSESSID=" . $this->session_id),
                    CURLOPT_POST => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HEADER => true,
                    CURLOPT_AUTOREFERER => true,
                ));
                ${$cycle} = curl_exec($ch);
                $this->flotta_datas[$cycle] = ${$cycle};
                $this->browser_emulation('companies');
            break;
        
            case 'companies':
                curl_setopt_array($ch, array(
                    CURLOPT_URL => 'http://iroda.elitflotta.hu/lists.php?q=c',
                    CURLOPT_RETURNTRANSFER => true,                            
                    CURLOPT_HTTPHEADER=> array("Cookie: PHPSESSID=" . $this->session_id),
                    CURLOPT_POST => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HEADER => true,
                    CURLOPT_AUTOREFERER => true,
                ));
                ${$cycle} = curl_exec($ch);
                $this->flotta_datas[$cycle] = ${$cycle};
            break;
        
            case 'data_pages':
                $url = 'http://iroda.elitflotta.hu/reszletek.php?sz=' . $postdata['sz'] . '&q=' . $postdata['q'];
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,                            
                    CURLOPT_HTTPHEADER=> array("Cookie: PHPSESSID=" . $this->session_id),
                    CURLOPT_POST => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HEADER => true,
                    CURLOPT_AUTOREFERER => true,
                ));
                ${$cycle} = curl_exec($ch);
                $this->data_page = ${$cycle};
            break;
        }
    }
    
    public function html_head()
    {
        $return = '<head>'
                . '<title>Jud-IT</title>'
                . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
                . '<meta name="Robots" content="NOINDEX,NOFOLLOW">'
                . '<link rel="stylesheet" href="css/elitflotta.css" />'
                . '<script src="jquery/js/jquery.min.js" type="text/javascript"></script>'
                . '<script src="js/elitflotta.js" type="text/javascript"></script>'
                . '</head>';
        return $return;
    }
}

$export_obj = new elitflotta_export();
print($export_obj->start());

?>