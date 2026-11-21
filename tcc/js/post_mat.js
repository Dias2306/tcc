  const radios       = document.querySelectorAll('input[name="tipo"]');
  const blocoArquivo = document.getElementById('bloco-arquivo');
  const blocoLink    = document.getElementById('bloco-link');
  const campoArquivo = document.getElementById('campo-arquivo');
  const campoLink    = document.getElementById('campo-link');

  // Mostra só o campo do tipo escolhido
  function atualizar()
  {
    const ehLink = document.querySelector('input[name="tipo"]:checked').value === 'link';

    blocoArquivo.style.display = ehLink ? 'none' : 'block';
    blocoLink.style.display    = ehLink ? 'block' : 'none';

    // campo escondido não pode ser "required", senão o navegador trava o envio
    campoArquivo.required = !ehLink;
    campoLink.required    = ehLink;
  }

  radios.forEach(function (r) {
    r.addEventListener('change', atualizar);
  });

  atualizar();