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
 *
 */
(function($)
{
    /**
     *
     */
    $.fn.tablesorter = function()
    {
        var $table = this;

        this.find("th").click(function()
        {
            var idx = $(this).index();
            var direction = $(this).hasClass("sort_asc");

            $table.tablesortby(idx,direction);
        });

        return this;
    };

    /**
     *
     */
    $.fn.tablesortby = function(idx,direction)
    {
        var $rows = this.find("tbody tr");

        function elementToVal(a)
        {
            var $a_elem = $(a).find("td:nth-child(" + (idx + 1) + ")");
            var a_val = $a_elem.attr("data-sort") || $a_elem.text();

            return (a_val == parseInt(a_val) ? parseInt(a_val) : a_val);
        }

        $rows.sort(function(a, b)
        {
            var a_val = elementToVal(a), b_val = elementToVal(b);

            return (a_val > b_val ? 1 : (a_val == b_val ? 0 : -1)) * (direction ? 1 : -1);
        });

        this.find("th").removeClass("sort_asc sort_desc");
        $(this).find("thead th:nth-child(" + (idx + 1) + ")").addClass(direction ? "sort_desc" : "sort_asc");

        for (var i = 0; i < $rows.length; i++)
        {
            this.append($rows[i]);
        }

        this.settablesortmarkers();

        return this;
    }

    /**
     *
     */
    $.fn.retablesort = function()
    {
        var $e = this.find("thead th.sort_asc, thead th.sort_desc");

        if ($e.length)
        {
            this.tablesortby($e.index(), $e.hasClass("sort_desc"));
        }

        return this;
    }

    /**
     *
     */
    $.fn.settablesortmarkers = function()
    {
        /**
         * Icone Sort ASC.
         */
        function icon_sort_asc()
        {
            return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 320 512\"><path d=\"M182.6 41.4c-12.5-12.5-32.8-12.5-45.3 0l-128 128c-9.2 9.2-11.9 22.9-6.9 34.9s16.6 19.8 29.6 19.8H288c12.9 0 24.6-7.8 29.6-19.8s2.2-25.7-6.9-34.9l-128-128z\"/></svg>";
        }

        /**
         * Icone Sort DESC.
         */
        function icon_sort_desc()
        {
            return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 320 512\"><path d=\"M182.6 470.6c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-9.2-9.2-11.9-22.9-6.9-34.9s16.6-19.8 29.6-19.8H288c12.9 0 24.6 7.8 29.6 19.8s2.2 25.7-6.9 34.9l-128 128z\"/></svg>";
        }

        this.find("thead th span.indicator").remove();
        this.find("thead th.sort_asc").append("<span class=\"indicator\">" + icon_sort_asc() + "<span>");
        this.find("thead th.sort_desc").append("<span class=\"indicator\">" + icon_sort_desc() + "<span>");

        return this;
    }
})(jQuery);

/**
 *
 */
$(function()
{
    var XSRF = (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)')||0)[2];
    var MAX_UPLOAD_SIZE = 4092;
    var $tbody = $('#list');

    $(window).on("hashchange", list).trigger("hashchange");
    $("#table").tablesorter();

    /**
     *
     */
    $("#table").on("click", ".delete", function(data)
    {
        $.post("", {
            "do": "delete",
            file: $(this).attr("data-file"),
            xsrf:XSRF
        }, function(response)
        {
            list();
        }, "json");

        return false;
    });

    /**
     *
     */
    $("#mkdir").submit(function(e)
    {
        var hashval = decodeURIComponent(window.location.hash.substr(1));
        var $dir = $(this).find("[name=name]");

        e.preventDefault();

        $dir.val().length && $.post("?", {
            "do": "mkdir",
            name: $dir.val(),
            xsrf: XSRF,
            file: hashval
        }, function(data)
        {
            list();
        }, "json");

        $dir.val("");

        return false;
    });

    /**
     * Coisas de upload de arquivo.
     */
    $("#file_drop_target")
        .on("dragover", function()
        {
            $(this).addClass("drag_over");

            return false;
        })
        .on("dragend", function()
        {
            $(this).removeClass("drag_over");

            return false;
        })
        .on("drop", function(e)
        {
            e.preventDefault();

            var files = e.originalEvent.dataTransfer.files;

            $.each(files, function(k, file)
            {
                uploadFile(file);
            });

            $(this).removeClass("drag_over");
        });

    $("input[type=file]").change(function(e)
    {
        e.preventDefault();

        $.each(this.files, function(k, file)
        {
            uploadFile(file);
        });
    });

    /**
     *
     */
    function uploadFile(file)
    {
        var folder = decodeURIComponent(window.location.hash.substr(1));

        if (file.size > MAX_UPLOAD_SIZE)
        {
            var $error_row = renderFileSizeErrorRow(file, folder);

            $("#upload_progress").append($error_row);

            window.setTimeout(function()
            {
                $error_row.fadeOut();
            }, 5000);

            return false;
        }

        var $row = renderFileUploadRow(file, folder);

        $("#upload_progress").append($row);

        var fd = new FormData();
            fd.append("file_data", file);
            fd.append("file", folder);
            fd.append("xsrf", XSRF);
            fd.append("do", "upload");

        var xhr = new XMLHttpRequest();
            xhr.open("POST", "?");
            xhr.onload = function()
            {
                $row.remove();
                list();
            };

            xhr.upload.onprogress = function(e)
            {
                if (e.lengthComputable)
                {
                    $row.find(".progress").css("width", (e.loaded / e.total * 100 | 0) + "%");
                }
            };

            xhr.send(fd);
    }

    /**
     *
     */
    function renderFileUploadRow(file, folder)
    {
        return $row = $("<div/>")
            .append($("<span class=\"fileuploadname\">").text((folder ? folder + "/" : "") + file.name))
            .append($("<div class=\"progress_track\"><div class=\"progress\"></div></div>"))
            .append($("<span class=\"size\">").text(formatFileSize(file.size)));
    }

    /**
     *
     */
    function renderFileSizeErrorRow(file, folder)
    {
        return $row = $("<div class=\"error\">")
            .append($("<span class=\"fileuploadname\">").text("Falha: " + (folder ? folder + "/" : "") + file.name))
            .append($("<span>").html(' tamanho do arquivo - <b>' + formatFileSize(file.size) + '</b>' + " excede o tamanho máximo de upload de <b>" + formatFileSize(MAX_UPLOAD_SIZE) + "</b>"));
    }

    /**
     *
     */
    function list()
    {
        var hashval = window.location.hash.substr(1);

        $.get("?do=list&file=" + hashval, function(data)
        {
            $tbody.empty();
            $("#breadcrumb").empty();
            renderBreadcrumbs(hashval);

            if (data.success)
            {
                $.each(data.results, function(k, v)
                {
                    $tbody.append(renderFileRow(v));
                });

                !data.results.length && $tbody.append("<tr><td class=\"empty\" colspan=5>Esta pasta está vazia</td></tr>");
                 data.is_writable ? $("body").removeClass("no_write") : $("body").addClass("no_write");
            } else
            {
                console.warn(data.error.msg);
            }

            $("#table").retablesort();
        },'json');
    }

    /**
     *
     */
    function renderFileRow(data)
    {
        var $link = $("<a class=\"name\">")
            .attr("href", data.is_dir ? '#' + encodeURIComponent(data.path) : "./" + data.path)
            .text(data.name);

        /**
         *
         */
        var ext_allow_direct_link;

        /**
         *
         */
        $.getJSON("?API-MODE=true", {}).done(function(data)
        {
            ext_allow_direct_link = data.allow_direct_link;
        });

        var allow_direct_link = ext_allow_direct_link;

        if (!data.is_dir && !allow_direct_link)
        {
            $link.css("pointer-events", "none");
        }

        var $dl_link = $("<a>")
            .attr("href", "?do=download&file=" + encodeURIComponent(data.path))
            .addClass("download").text("download");

        var $delete_link = $("<a href=\"#\" />")
            .attr("data-file", data.path)
            .addClass("delete")
            .text("delete");

        var perms = [];

        if (data.is_readable)
        {
            perms.push("read");
        }

        if (data.is_writable)
        {
            perms.push("write");
        }

        if (data.is_executable)
        {
            perms.push("exec");
        }

        var $html = $("<tr>")
            .addClass(data.is_dir ? "is_dir" : "")
            .append($("<td class=\"first\">").append($link))
            .append($("<td>").attr("data-sort", data.is_dir ? -1 : data.size)
            .html($("<span class=\"size\">").text(formatFileSize(data.size))))
            .append($("<td>").attr("data-sort", data.mtime).text(formatTimestamp(data.mtime)))
            .append($("<td>").text(perms.join("+")))
            .append($("<td>").append($dl_link).append(data.is_deleteable ? $delete_link : ""))

        return $html;
    }

    /**
     *
     */
    function renderBreadcrumbs(path)
    {
        var base = "";
        var idxname = "Índice de /";
        $("#breadcrumb").append("<li><a href=\"#\">Início</a></li>");

        $.each(path.split('%2F'), function(k, v)
        {
            if (v)
            {
                var v_as_text = decodeURIComponent(v);

                $("#breadcrumb").append("<li><a href=\"#" + base + v + "\">"+ v_as_text +"</a></li>");
                idxname += v_as_text + "/";

                base += v + '%2F';
            }
        });

        $("html head").find("title").text(idxname);
        $("#idxtitle").text(idxname);
    }

    /**
     *
     */
    function formatTimestamp(unix_timestamp)
    {
        var m = [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec"
        ];

        var d = new Date(unix_timestamp * 1000);

        return [
            m[
                d.getMonth()
            ], " ",
            d.getDate(), ", ",
            d.getFullYear(), " ",
            (
                d.getHours() % 12 || 12
            ), ":",
            (
                d.getMinutes() < 10 ? "0" : ""
            ) + d.getMinutes(), " ",
            d.getHours() >= 12 ? "PM" : "AM"].join("");
    }

    /**
     *
     */
    function formatFileSize(bytes)
    {
        var s = [
            "bytes",
            "KB",
            "MB",
            "GB",
            "TB",
            "PB",
            "EB"
        ];

        for (var pos = 0; bytes >= 1000; pos++, bytes /= 1024)
        {
        }

        var d = Math.round(bytes * 10);

        return pos ? [parseInt(d / 10), ".", d % 10, " ", s[pos]].join("") : bytes + " bytes";
    }
});
