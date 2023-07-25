<?php

    /**
     * Direito Autoral (C) {{ ano(); }}  Marisinha
     *
     * Este programa é um software livre: você pode redistribuí-lo
     * e/ou modificá-lo sob os termos da Licença Pública do Cavalo
     * publicada pela Fundação do Software Brasileiro, seja a versão
     * 3 da licença ou (a seu critério) qualquer versão posterior.
     *
     * Este programa é distribuído na esperança de que seja útil,
     * mas SEM QUALQUER GARANTIA; mesmo sem a garantia implícita de
     * COMERCIABILIDADE ou ADEQUAÇÃO PARA UM FIM ESPECÍFICO. Consulte
     * a Licença Pública e Geral do Cavalo para obter mais detalhes.
     *
     * Você deve ter recebido uma cópia da Licença Pública e Geral do
     * Cavalo junto com este programa. Se não, consulte:
     *   <http://localhost/licenses>.
     */

    /**
     * Desativar relatório de falhas.
     */
    error_reporting(E_ERROR | E_PARSE);

    /**
     * Opções de Segurança.
     */

    /**
     * Defina como false para desativar o botão de remoção
     * e remover a solicitação POST.
     */
    $allow_delete = false;

    /**
     * Defina como true para permitir o upload de arquivos.
     */
    $allow_upload = false;

    /**
     * Defina como false para desativar a criação de pasta.
     */
    $allow_create_folder = false;

    /**
     * Defina como false para permitir apenas downloads e
     * não link direto.
     */
    $allow_direct_link = true;

    /**
     * Defina como false para ocultar todos os diretórios.
     */
    $allow_show_folders = true;

    /**
     *
     */
    $disallowed_patterns = [];

    /**
     * Extensões ocultas no índice do diretório.
     */
    $hidden_patterns = [];

    /**
     * Defina uma senha, para acessar o gerenciador de arquivos (opcional).
     */
    $password = "";

    /**
     *
     */
    if ($password)
    {
        session_start();

        if (!$_SESSION["_sfm_allowed"])
        {
            /**
             * sha1, e bytes aleatórios para impedir sequências
             * de temporização. Não significa como hash seguro.
             */
            $t = bin2hex(
                openssl_random_pseudo_bytes(10)
            );

            /**
             *
             */
            if ($_POST["p"] && sha1($t.$_POST["p"]) === sha1($t.$password))
            {
                $_SESSION["_sfm_allowed"] = true;

                header("Location: ?");
            }

            /**
             * Permite enviar o formulário de login para a pessoa.
             */
            include __DIR__ . "/login.php";
            exit;
        }
    }

    /**
     * Deve estar em UTF-8 ou `basename` pode não funcionar.
     */
    setlocale(LC_ALL, "pt_BR.UTF-8");

    /**
     *
     */
    $tmp_dir = dirname(
        $_SERVER["SCRIPT_FILENAME"]
    );

    /**
     *
     */
    if (DIRECTORY_SEPARATOR === "\\")
    {
        $tmp_dir = str_replace("/", DIRECTORY_SEPARATOR, $tmp_dir);
    }

    /**
     *
     */
    $tmp = get_absolute_path($tmp_dir . "/" . $_REQUEST["file"]);

    /**
     *
     */
    if ($tmp === false)
    {
        err(404, "Arquivo ou diretório não encontrado");
    }

    /**
     *
     */
    if (substr($tmp, 0, strlen($tmp_dir)) !== $tmp_dir)
    {
        err(403, "Proibido");
    }

    /**
     *
     */
    if (strpos($_REQUEST["file"], DIRECTORY_SEPARATOR) === 0)
    {
        err(403, "Proibido");
    }

    /**
     *
     */
    if (preg_match("@^.+://@", $_REQUEST["file"]))
    {
        err(403, "Proibido");
    }

    /**
     *
     */
    if (!$_COOKIE["_sfm_xsrf"])
    {
        setcookie("_sfm_xsrf", bin2hex(openssl_random_pseudo_bytes(16)));
    }

    /**
     *
     */
    if ($_POST)
    {
        if($_COOKIE["_sfm_xsrf"] !== $_POST["xsrf"] || !$_POST["xsrf"])
        {
            err(403, "XSRF Failure");
        }
    }

    /**
     *
     */
    $file = $_REQUEST["file"] ?: ".";

    /**
     *
     */
    if ($_GET["do"] == "list")
    {
        if (is_dir($file))
        {
            $directory = $file;
            $result = [];
            $files = array_diff(scandir($directory), [".", ".."]);

            foreach ($files as $entry)
            {
                if (!is_entry_ignored($entry, $allow_show_folders, $hidden_patterns))
                {
                    $i = $directory . "/" . $entry;
                    $stat = stat($i);

                    $result[] = [
                        "mtime" => $stat["mtime"],
                        "size" => $stat["size"],
                        "name" => basename($i),
                        "path" => preg_replace("@^\./@", "", $i),
                        "is_dir" => is_dir($i),

                        "is_deleteable" => $allow_delete && (
                            (
                                !is_dir($i) &&
                                 is_writable($directory)
                            ) ||
                            (
                                is_dir($i) &&
                                is_writable($directory) &&
                                is_recursively_deleteable($i)
                            )
                        ),

                        "is_readable" => is_readable($i),
                        "is_writable" => is_writable($i),
                        "is_executable" => is_executable($i)
                    ];
                }
            }

            usort($result, function($f1, $f2)
            {
                $f1_key = ($f1["is_dir"] ?: 2) . $f1["name"];
                $f2_key = ($f2["is_dir"] ?: 2) . $f2["name"];

                return $f1_key > $f2_key;
            });
        } else
        {
            err(412, "Não é um diretório");
        }

        echo json_encode([
            "success" => true,
            "is_writable" => is_writable($file),
            "results" =>$result
        ]);

        exit;
    } elseif ($_POST["do"] == "delete")
    {
        if ($allow_delete)
        {
            rmrf($file);
        }

        exit;
    } elseif ($_POST["do"] == "mkdir" && $allow_create_folder)
    {
        /**
         * Não permita ações fora da raiz. também filtramos as
         * barras para capturar argumentos como './../externo'.
         */
        $dir = $_POST["name"];
        $dir = str_replace("/", "", $dir);

        if (substr($dir, 0, 2) === "..")
        {
            exit;
        }

        chdir($file);
        @mkdir($_POST["name"]);
        exit;
    } elseif ($_POST["do"] == "upload" && $allow_upload)
    {
        foreach ($disallowed_patterns as $pattern)
        {
            if (fnmatch($pattern, $_FILES["file_data"]["name"]))
            {
                err(403, "Arquivos deste tipo não são permitidos.");
            }
        }

        $res = move_uploaded_file(
            $_FILES["file_data"]["tmp_name"],
            $file . "/" . $_FILES["file_data"]["name"]
        );

        exit;
    } elseif ($_GET["do"] == "download")
    {
        foreach ($disallowed_patterns as $pattern)
        {
            if (fnmatch($pattern, $file))
            {
                err(403, "Arquivos deste tipo não são permitidos.");
            }
        }

        $filename = basename($file);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        header("Content-Type: " . finfo_file($finfo, $file));
        header("Content-Length: " . filesize($file));
        header(
            sprintf("Content-Disposition: attachment; filename=%s",
            strpos("MSIE", $_SERVER["HTTP_REFERER"]) ? rawurlencode($filename) : "\"$filename\"")
        );

        ob_flush();
        readfile($file);
        exit;
    }

    /**
     *
     */
    function is_entry_ignored($entry, $allow_show_folders, $hidden_patterns)
    {
        if ($entry === basename(__FILE__))
        {
            return true;
        }

        if (is_dir($entry) && !$allow_show_folders)
        {
            return true;
        }

        foreach ($hidden_patterns as $pattern)
        {
            if (fnmatch($pattern, $entry))
            {
                return true;
            }
        }

        return false;
    }

    /**
     *
     */
    function rmrf($dir)
    {
        if (is_dir($dir))
        {
            $files = array_diff(scandir($dir), [".", ".."]);

            foreach ($files as $file)
            {
                rmrf("$dir/$file");
            }

            rmdir($dir);
        } else
        {
            unlink($dir);
        }
    }

    /**
     *
     */
    function is_recursively_deleteable($d)
    {
        $stack = [$d];

        while ($dir = array_pop($stack))
        {
            if (!is_readable($dir) || !is_writable($dir))
            {
                return false;
            }

            $files = array_diff(scandir($dir), [".", ".."]);

            foreach ($files as $file)
            {
                if (is_dir($file))
                {
                    $stack[] = "$dir/$file";
                }
            }
        }

        return true;
    }

    /**
     *
     */
    function get_absolute_path($path)
    {
        $path = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $path);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $absolutes = [];

        foreach ($parts as $part)
        {
            if ("." == $part)
            {
                continue;
            }

            if (".." == $part)
            {
                array_pop($absolutes);
            } else
            {
                $absolutes[] = $part;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    /**
     *
     */
    function err($code, $msg)
    {
        http_response_code($code);
        header("Content-Type: application/json");

        echo json_encode([
            "error" => [
                "code" => intval($code),
                "msg" => $msg
            ]
        ]);

        exit;
    }

    /**
     *
     */
    function asBytes($ini_v)
    {
        $ini_v = trim($ini_v);
        $s = [
            "g" => 1<<30,
            "m" => 1<<20,
            "k" => 1<<10
        ];

        return intval($ini_v) * ($s[strtolower(substr($ini_v, -1))] ?: 1);
    }

    /**
     * Obter o titulo da página atual.
     */
    function getTitle()
    {
        $output = "Índice de /";

        /**
         * Incrementar a pasta de sub página aqui.
         */
        if (false)
        {
            $output .= "";
        }

        return $output;
    }

    /**
     *
     */
    $MAX_UPLOAD_SIZE = min(
        asBytes(ini_get("post_max_size")),
        asBytes(ini_get("upload_max_filesize"))
    );

    /**
     * Questão: Devolver uma API Json ou renderizar a página ?
     */
    if (strtolower($_GET["API-MODE"]) == "true")
    {
        /**
         * O layout da visualização.
         */
        include __DIR__ . "/layout/api.php";
    } else
    {
        /**
         * O layout da visualização.
         */
        include __DIR__ . "/layout/view.php";
    }
