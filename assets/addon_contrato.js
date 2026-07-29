/** 
* ABAIXO COMO INCLUIR ITEM NO MENU DO SISTEMA, DESCOMENTE AS LINHAS ABAIXO E LIMPE CACHE
* DO NAVEGADOR QUE NO MENU PROVEDOR SERA INCLUSO UM LINK PARA ADDON TESTE DE EXEMPLO:
*
*/

//CONTRATOS
const updata = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port: '') + "/admin/addons/";
add_menu.clientes(`{ "plink": "${updata}contratos", "ptext": "Contratos Assinados"}`)
