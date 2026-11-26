// Alterna entre "Enviar arquivo" e "Link de vídeo":
// com "link" marcado, some o campo de arquivo e aparece só o campo de texto do link.

(function ()
{
    var radios       = document.querySelectorAll('input[name="tipo"]');
    var blocoArquivo = document.getElementById('bloco-arquivo');
    var blocoLink    = document.getElementById('bloco-link');
    var campoArquivo = document.getElementById('campo-arquivo');
    var campoLink    = document.getElementById('campo-link');

    function atualizar()
    {
        var marcado = document.querySelector('input[name="tipo"]:checked');
        var usaLink = marcado !== null && marcado.value === 'link';

        // mostra um bloco e esconde o outro
        blocoArquivo.style.display = usaLink ? 'none' : '';
        blocoLink.style.display    = usaLink ? '' : 'none';

        // o campo escondido não pode ser "required" (senão o navegador bloqueia o envio)
        // e, desabilitado, também não vai junto no formulário
        campoArquivo.required = !usaLink;
        campoArquivo.disabled = usaLink;

        campoLink.required = usaLink;
        campoLink.disabled = !usaLink;
    }

    for (var i = 0; i < radios.length; i++)
    {
        radios[i].addEventListener('change', atualizar);
    }

    // já ajusta ao abrir a página (por exemplo, quando volta de um erro com "link" marcado)
    atualizar();
})();