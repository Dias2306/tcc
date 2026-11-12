 function abrirMenu() {
    document.querySelector(".lateral-menu").style.width = "250px";
    document.getElementById("principal").style.marginRight = "250px";  // era marginLeft
  }

  function fecharMenu() {
    document.querySelector(".lateral-menu").style.width = "0";
    document.getElementById("principal").style.marginRight = "0";      // era marginLeft
  }

    const botao = document.getElementById('botao-flutuante');
  const menu = document.getElementById('mini-menu');
 
  // Ao clicar no botão: esconde o botão e mostra o menu
  botao.addEventListener('click', function (e) {
    e.stopPropagation();
    botao.style.display = 'none';
    menu.classList.add('aberto');
  });
 
  // Ao clicar fora do menu: fecha o menu e o botão volta
  document.addEventListener('click', function (e) {
    if (!menu.contains(e.target)) {
      menu.classList.remove('aberto');
      botao.style.display = 'block';
    }
  });

    const hoje = new Date();
 
  // Nome do dia da semana em português (ex.: "quarta-feira")
  let diaSemana = hoje.toLocaleDateString('pt-BR', { weekday: 'long' });
 
  // Deixa a primeira letra maiúscula
  diaSemana = diaSemana.charAt(0).toUpperCase() + diaSemana.slice(1);
 
  // Data completa (ex.: "11/11/2026")
  const data = hoje.toLocaleDateString('pt-BR');
 
  document.getElementById('dia-semana').textContent = diaSemana;
  document.getElementById('data').textContent = data;


  // true  = usa dados de teste (funciona sem servidor)
  // false = busca no PHP (buscar_horarios.php)
  const USAR_DADOS_TESTE = true;
 
  const dadosTeste = {
    aulas: [
      { hora_inicio: "07:30:00", hora_fim: "08:20:00", materia: "Programação Web", turma: "DS-2A", sala: "Lab 3" },
      { hora_inicio: "08:20:00", hora_fim: "09:10:00", materia: "Programação Web", turma: "DS-2A", sala: "Lab 3" },
      { hora_inicio: "09:30:00", hora_fim: "10:20:00", materia: "Banco de Dados", turma: "DS-3B", sala: "Sala 12" }
    ]
  };
 
  const titulo = document.getElementById('titulo');
  const dataEl = document.getElementById('data');
  const lista  = document.getElementById('lista');
 
  // Dia da semana e data no topo do card


  // "07:30:00" -> "07:30"
  function hhmm(hora) {
    return hora.substring(0, 5);
  }
 
  // Verifica se a aula está acontecendo agora
  function estaAgora(aula) {
    const agora = hoje.toTimeString().substring(0, 5);
    return agora >= hhmm(aula.hora_inicio) && agora < hhmm(aula.hora_fim);
  }
 
  function mostrarAulas(aulas) {
    lista.innerHTML = '';
 
    if (aulas.length === 0) {
      lista.innerHTML = '<div class="vazio">Sem aulas hoje 🎉</div>';
      return;
    }
 
    aulas.forEach(function (aula) {
      const div = document.createElement('div');
      div.className = 'aula' + (estaAgora(aula) ? ' agora' : '');
 
      const horario = document.createElement('div');
      horario.className = 'horario';
      horario.textContent = hhmm(aula.hora_inicio) + ' - ' + hhmm(aula.hora_fim);
 
      const info = document.createElement('div');
 
      const materia = document.createElement('div');
      materia.className = 'materia';
      materia.textContent = aula.materia;
 
      const detalhe = document.createElement('div');
      detalhe.className = 'detalhe';
      detalhe.textContent = aula.turma + ' • ' + aula.sala;
 
      info.appendChild(materia);
      info.appendChild(detalhe);
      div.appendChild(horario);
      div.appendChild(info);
      lista.appendChild(div);
    });
  }
 
  function mostrarErro(msg) {
    lista.innerHTML = '<div class="erro">' + msg + '</div>';
  }
 
  // Busca os horários
  if (USAR_DADOS_TESTE) {
    mostrarAulas(dadosTeste.aulas);
  } else {
    fetch('buscar_horarios.php')
      .then(function (resp) { return resp.json(); })
      .then(function (dados) {
        if (dados.erro) {
          mostrarErro(dados.erro);
        } else {
          mostrarAulas(dados.aulas);
        }
      })
      .catch(function () {
        mostrarErro('Não foi possível carregar os horários.');
      });
  }