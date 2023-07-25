<!DOCTYPE html>
<html lang="pt-br">
<head>

    <!--
      - Charset.
     -->
    <meta charset="utf-8">

    <!--
      - Viewport.
     -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!--
      - Título.
     -->
    <title><?= getTitle() ?></title>

    <!--
      - Estilos.
     -->
    <link rel="stylesheet" type="text/css" href="/php-explorer/dist/css/style.css">

</head>
<body>

    <div class="container">
        <div class="row" style="background: none; box-shadow: none; padding: 0; border-radius: 0;">
            <div class="col col-xs-12">
                <h1>
                    <strong id="idxtitle">
                        <?= getTitle() ?>
                    </strong>
                </h1>
            </div>
        </div>

        <div class="row">
            <div class="col col-xs-12 clearfix">
                <ol id="breadcrumb" class="breadcrumb">
                    <li><a href="#">/</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col col-xs-12">
                <table class="table table-bordered table-striped table-condensed" id="table">
                    <thead>
                        <tr>
                            <th>
                                Nome
                            </th>

                            <th>
                                Tamanho
                            </th>

                            <th>
                                Modificado
                            </th>

                            <th>
                                Permissões
                            </th>

                            <th>
                                Ações
                            </th>
                        </tr>
                    </thead>

                    <tbody id="list">
                    </tbody>

                    <thead>
                        <tr>
                            <th>
                                Nome
                            </th>

                            <th>
                                Tamanho
                            </th>

                            <th>
                                Modificado
                            </th>

                            <th>
                                Permissões
                            </th>

                            <th>
                                Ações
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <?php if ($allow_upload || $allow_create_folder): ?>
            <div id="top" class="row">
        <?php endif ?>
            <?php if($allow_upload): ?>
                <div class="col col-xs-12 col-md-8 clearfix" style="height: 108px;">
                    <div id="file_drop_target">
                        Arraste os arquivos aqui para fazer o upload

                        <input class="btn" type="file" multiple>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($allow_create_folder): ?>
                <div class="col col-xs-12 col-md-4 clearfix" style="height: 108px;">
                    <form action="?" method="POST" id="mkdir">
                        <div class="form-group">
                            <label for="dirname">
                                Criar nova pasta
                            </label>
                            <input id="dirname" class="form-control" type="text" name="name" value="">
                        </div>

                        <input type="submit" class="btn btn-default" value="Criar">
                    </form>
                </div>
            <?php endif; ?>
        <?php if ($allow_upload || $allow_create_folder): ?>
            </div>
        <?php endif ?>

        <?php if($allow_upload): ?>
            <div class="row">
                <div class="col col-xs-12">
                    <div id="upload_progress"></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row" style="background: none; box-shadow: none; padding: 0; border-radius: 0;">
            <div class="col col-xs-12">
                <?php
                    echo $_SERVER["SERVER_SIGNATURE"];
                ?>
            </div>
        </div>
    </div>


    <!--
      - Scripts.
     -->
    <script type="text/javascript" src="/php-explorer/dist/js/jquery-1.7.2.js"></script>
    <script type="text/javascript" src="/php-explorer/dist/js/script<?php if ($allow_upload) { echo "-with-upload"; } ?>.js"></script>

</body>
</html>
