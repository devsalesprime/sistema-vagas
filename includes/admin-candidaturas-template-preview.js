jQuery(document).ready(function($) {
    $("#visualizar-template").on("click", function(e) {
        e.preventDefault();

        var assunto = $("#assunto").val();
        var mensagemContent = "";

        if (typeof tinymce !== "undefined" && tinymce.get("mensagem")) {
            mensagemContent = tinymce.get("mensagem").getContent();
        } else {
            mensagemContent = $("#mensagem").val();
        }

        var dadosExemplo = {
            "{nome}": "João da Silva",
            "{vaga}": "Desenvolvedor(a) Sênior",
            "{empresa}": "" + sv_admin_template_vars.blog_name + "",
            "{observacoes}": "<strong>Observações:</strong><p>Candidato com excelente portfólio. Agendar entrevista.</p>",
            "{data}": "" + sv_admin_template_vars.current_date + "",
            "{site}": "" + sv_admin_template_vars.blog_name + "",
            "{url}": "" + sv_admin_template_vars.blog_url + ""
        };

        for (var tag in dadosExemplo) {
            var regex = new RegExp(tag.replace(/[.*+?^${}()|[\]\\]/g, "\\\\$&"), "g");
            assunto = assunto.replace(regex, dadosExemplo[tag]);
            mensagemContent = mensagemContent.replace(regex, dadosExemplo[tag]);
        }

        var previewWindow = window.open("", "TemplatePreview", "width=800,height=600,scrollbars=yes,resizable=yes");
        
        if (previewWindow) {
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Visualização: ${assunto}</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; margin: 0; padding: 40px; background: #f0f0f1; color: #1d2327; }
                        .email-container { max-width: 600px; margin: 0 auto; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
                        .email-header { background: #2271b1; color: #fff; padding: 20px; }
                        .email-header h1 { font-size: 24px; margin: 0; color: #fff; }
                        .email-content { padding: 30px; line-height: 1.6; }
                        .email-footer { padding: 20px; text-align: center; font-size: 12px; color: #787c82; border-top: 1px solid #c3c4c7; }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <div class="email-header"><h1>${assunto}</h1></div>
                        <div class="email-content">${mensagemContent}</div>
                        <div class="email-footer">Esta é apenas uma visualização.</div>
                    </div>
                </body>
                </html>
            `);
            previewWindow.document.close();
            previewWindow.focus();
        } else {
            alert("Seu navegador bloqueou a janela de visualização. Por favor, permita pop-ups para este site.");
        }
    });
});

