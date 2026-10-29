
const principal = document.querySelector(".principal");
const brilhos = document.querySelectorAll(".brilho");

const movimentoReduzido = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
);

if (principal && !movimentoReduzido.matches) {
    let quadroPendente = false;
    let ultimaPosicao = null;

    // Luz que acompanha o cursor
    principal.addEventListener("pointermove", (evento) => {
        if (evento.pointerType === "touch") return;

        const retangulo = principal.getBoundingClientRect();

        ultimaPosicao = {
            x: evento.clientX - retangulo.left,
            y: evento.clientY - retangulo.top,
            largura: retangulo.width,
            altura: retangulo.height
        };

        if (!quadroPendente) {
            quadroPendente = true;

            requestAnimationFrame(() => {
                if (ultimaPosicao) {
                    const { x, y, largura, altura } = ultimaPosicao;

                    principal.style.setProperty(
                        "--mouse-x",
                        `${x}px`
                    );

                    principal.style.setProperty(
                        "--mouse-y",
                        `${y}px`
                    );

                    // Movimento sutil dos brilhos
                    brilhos.forEach((brilho, indice) => {
                        const direcao = indice === 0 ? 1 : -1;

                        brilho.style.transform = `
                            translate(
                                ${(x / largura - 0.5) * 24 * direcao}px,
                                ${(y / altura - 0.5) * 24 * direcao}px
                            )
                        `;
                    });
                }

                quadroPendente = false;
            });
        }

        principal.classList.add("mouse-ativo");
    });

    // Esconde a iluminação quando o mouse sai
    principal.addEventListener("pointerleave", () => {
        principal.classList.remove("mouse-ativo");

        brilhos.forEach((brilho) => {
            brilho.style.transform = "translate(0, 0)";
        });
    });

    // Onda luminosa ao clicar no fundo
    principal.addEventListener("click", (evento) => {
        // Não cria ondas sobre os botões ou links
        if (evento.target.closest("a, button")) return;

        const retangulo = principal.getBoundingClientRect();

        const onda = document.createElement("span");

        onda.className = "onda-clique";

        onda.style.left =
            `${evento.clientX - retangulo.left}px`;

        onda.style.top =
            `${evento.clientY - retangulo.top}px`;

        principal.appendChild(onda);

        onda.addEventListener("animationend", () => {
            onda.remove();
        }, { once: true });
    });
}
