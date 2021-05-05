console.log(document.getElementById('pseudoJson').dataset.pseudos)

const searchInput = document.getElementById('name');

searchInput.addEventListener('input', event => {
    fetch('/admin/autocomplete/' + searchInput.value)
        .then(data => data.json())
        .then(pseudos => displayPseudo(pseudos))
})

const displayPseudo = pseudos => {
    const pseudosUL = document.getElementById('pseudos');
    pseudosUL.innerHTML = '';
    for (let i = 0; i < pseudos.length; i++) {
        let pseudoLi = document.createElement('li');
        pseudoLi.innerHTML = '<a href="/admin/show/' + pseudos[i].pseudo + '">' + pseudos[i].pseudo + '</a>';
        pseudosUL.appendChild(pseudoLi);
    }
}
