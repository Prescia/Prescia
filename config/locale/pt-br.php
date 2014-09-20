<? return array(
"bi_sessionnote"=>"Quando você alterar de site mudando o domains, use ?noSession=true para limpar a sessão/cache para o framework carregar o novo domínio",
"minlvltooptions"=>"Nível mínimo para alterar opções gerais",
'contactmail' => "Mail padrão de contato",
"nobenchstats" => "Páginas que não devem rodar monitoramento de performance",
"nostats" => "Páginas que não devem manter estatísticas",
"captcha_fail" => "O código de segurança foi entrado incorretamente",
"urla" => "URL amigável",
"new"=>"Novo",
"add"=>"Adicionar",
"insert" => "Inserir",
"register" => "Cadastrar",
"include"=>"Incluir",
"create" => "Criar",
"edit"=>"Editar",
"change" => "Mudar",
"delete"=>"Deletar",
"remove"=>"Remover",
"exclude" => "Excluir",
"erase" => "Apagar",
"list"=>"Listar",
"all" => "Todos",
"show" => "Mostrar",
"geral" => "Geral",
"see" => "Ver",
"search" => "Buscar",
"find" => "Encontrar",
"back"=>"Voltar",
"n"=>"Não",
"no" => "Não",
"y"=>"Sim",
"yes"=>"Sim",
"submit"=>"Enviar",
"id"=>"id",
"ip"=>"IP",
"session_manager"=>"Gerente de seção",
'revalidatecode'=>"Código de validação",
'id_user' => 'Usuário',
'hour' => 'hora',
'id_parent' => 'Agrupador',
'publish' => 'Publicar',
'ordem' => 'Ordem',
'locked' => 'Trancado',
'sendmail_sent_from' => "Enviado a partir de",
"fmanager" => "Gerenciador de arquivos",

"xmltype200" => "Número Inteiro",
"xmltype201" => "Numero Decimal",
"xmltype202" => "Pequeno Texto",
"xmltype203" => "Valores pré-definidos (ENUM)",
"xmltype204" => "Texto",
"xmltype205" => "Data",
"xmltype206" => "Data e hora",
"xmltype207" => "Upload",
"xmltype208" => "Link com outro módulo",
"xmltype209" => "String de opções 0/1",
"xmltype0" => "Link(s) com outro módulo",

"month" => "Mês",
"month01"=>"Janeiro",
"month02"=>"Fevereiro",
"month03"=>"Março",
"month04"=>"Abril",
"month05"=>"Maio",
"month06"=>"Junho",
"month07"=>"Julho",
"month08"=>"Agosto",
"month09"=>"Setembro",
"month10"=>"Outubro",
"month11"=>"Novembro",
"month12"=>"Dezembro",

"day" => "dia",
"days" => "dias",
"day0" => "Domingo",
"day1" => "Segunda",
"day2" => "Terça",
"day3" => "Quarta",
"day4" => "Quinta",
"day5" => "Sexta",
"day6" => "Sábado",

"day0sm" => "D",
"day1sm" => "S",
"day2sm" => "T",
"day3sm" => "Q",
"day4sm" => "Q",
"day5sm" => "S",
"day6sm" => "S",

//error codes
"e1"=>"Erro no loadMetadata ou loadPageSettings (inicialização)",
"e2"=>"Plugin não encontrado",
"e3"=>"Erro carregando cache de permissões",
"e4"=>"Plugin não pode rodar pois faltam recursos necessários",
"e5"=>"Plugin foi carregado com novo nome, mas sem redirecionar para novo módulo",
"e6"=>"Erro lendo cache de arquivo local",
		"e7"=>"Erro lendo arquivo local",

		"e100"=>"Arquivo de domínios está corrompido",
		"e101"=>"Domínio não encontrado",
		"e102"=>"Domínio não encontrado (cacheado)",
		"e103"=>"Erro 404",
		"e104"=>"Banco de dados fora do ar, tentando rodar offline",
		"e105"=>"Banco de dados fora do ar, abortando",
		"e106"=>"Cache de Metadados corrompido, tentando rodar em modo debug",
		"e107"=>"Cache do PageSettings corrompido, tentando rodar em modo debug",
		"e108"=>"Verificação de permissões incorreta (SQL e módulo não combinam)",
		"e109"=>"Último CRON deu timeout",
		"e110"=>"Quota excedida",
		"e111"=>"Timeout durante CRON-D",
		"e112"=>"Timeout durante CRON-H",
		"e113"=>"Executando otimização e backup do banco",
		"e114"=>"Página não encontrada no contexto/ação",
		"e115"=>"internalFoward abortado (configuração)",
		"e116"=>"Arquivo pages.xml corrompido",
		"e117"=>"Automato definido no pages.xml não encontrado",
		"e118"=>"Instalação esta corrompida",
		"e119"=>"Metadados (original ou união) está corrompido",
		"e120"=>"Existem dois módulos usando o mesmo banco de dados",
		"e121"=>"Um campo definido não possui tipo",
		"e122"=>"Tabela de dados não está presente e não foi possível criá-lo",
		"e123"=>"Erro aoc onectar no banco de dados",
		"e124"=>"Erro ao gravar cache de matadados",
		"e125"=>"Possível tentativa de hack (mysql injection)",
		"e126"=>"Um objeto foi enviado como ação no runAction",
		"e127"=>"Campos obrigatórios faltando",
		"e128"=>"Chave pái obrigatória faltando ou causaria loop",
		"e129"=>"Campo do tipo login inválido",
		"e130"=>"Campo do tipo email inválido",
		"e131"=>"Campo do tipo caminho/arquivo inválido",
		"e132"=>"Campo do tipo Youtube/vimeo inválido",
		"e133"=>"Campo do tipo hora inválido",
		"e134"=>"Campo do tipo data inválido",
		"e135"=>"HTML suspeito foi usado, provávelmente contém formatação colada de outro editor (word?)",
		"e136"=>"Erro ao atualizar um item",
		"e137"=>"Erro ao atualizar um item, ele já existe",
		"e138"=>"Nenhum SQL para inserir!",
		"e139"=>"Campo pai faltando durante criação",
		"e140"=>"Erro ao inserir um item",
		"e141"=>"Erro ao inserir um item, ele já existe",
		"e142"=>"Nenhum SQL para atualizar!",
		"e143"=>"Erro ao apagar um item",
		"e144"=>"Possível exploit ou mysql injection durante query",
		"e145"=>"",
		"e146"=>"Erro de SQL durante query",
		"e147"=>"Query lenta",
		"e148"=>"SELECT query sem chaves!",
		"e149"=>"Permissão negada para apagar",
		"e150"=>"Permissão negada para inserir",
		"e151"=>"Permissão negada para atualizar",
		"e152"=>"Permissão negada para ler",
		"e153"=>"Módulo não encontrado no checkPermission",
		"e154"=>"Tentativa de alterar item com a propriedade de outro usuário",
		"e155"=>"SQL e Módulo não combinam ao tentar verificar permissões",
		"e156"=>"Permissão negada ao checar credênciais",
		"e157"=>"Abortando ação devido à erro em cascata (anterior)",
		"e158"=>"Módulo não encontrado durante runContent",
		"e159"=>"Saída inexperada",
		"e160"=>"Carregando backup do dinconfig",
		"e161"=>"Carregando backup do statsconfig",
		"e162"=>"Erro carregando dincomfig",
		"e163"=>"Erro carregando metadados de um módulo",
		"e164"=>"O sistema tentou gravar um dinconfig corrompido",
		"e165"=>"Erro gravando dinconfig",
		"e166"=>"Erro 404 (fast)",
		"e167"=>"Script próximo do tempo limite",
		"e168"=>"Um plugin evitou alterações no banco de dados",
		"e169"=>"Erro ao tentar usar fast count no RunContent",
		"e170"=>"Script não encontrado",
		"e171"=>"Solicitação de arquivo multimídia (fmanager) chegou ao index.php",
		"e178"=>"Muitos erros, provável loop",
		"e179"=>"Erro 404 não conseguiu ser logado",
		"e180"=>"custom.xml corrompido",
		"e181"=>"Um campo de upload do custom.xml não tem o parâmetro location",
		"e182"=>"SQL chegou em formato array inválido",
		"e183"=>"Template de E-mail inválido",

		"e200"=>"Upload ok",
		"e201"=>"Arquivo maior do que o permitido",
		"e202"=>"Arquivo maior do que o permitido",
		"e203"=>"Upload incompleto",
		"e204"=>"Nenhum arquivo enviado",
		"e205"=>"Upload de extensão não permitida",
		"e207"=>"Arquivo não é o que a extensão informa",
		"e210"=>"Quota excedida ao tentar fazer upload",
		"e211"=>"Campo obrigatório não encontrado ou inválido",

		"e300"=>"Ação normal",
		"e301"=>"Login",
		"e302"=>"Logout",
		"e303"=>"Erro de login: inativo",
		"e304"=>"Erro de login: expirado",
		"e305"=>"Erro de login: login ou senha não conferem",
		"e306"=>"Ação não foi executada",

		"e400"=>"HTML para o automato FRAME não encontrado",
		"e401"=>"Erro no automato FriendlyURL",
		"e402"=>"Erro no automato Autoform",
		"e403"=>"Erro no automato Sendmail",
		"e404"=>"TITLE da página foi colocado na raiz (site inteiro), use o config para isto!",
		"e405"=>"Envio de E-mail via Sendmail",
		"e406"=>"Popin não encontrou o tag {bi_popin}",
		"e407"=>"Automato shares não tem o tag preenchido",
		"e498"=>"Erro genérico FATAL - automatos",
		"e499"=>"Erro genérico NÃO FATAL - automatos",



"waiting_ajax_results"=>"Consultando, aguarde ...",
"floodcontrol" => "Controle de fluxo",
"floodcontrol_pleasewait" => "Por favor aguarde um pouco antes de tentar a ação novamente, para não sobrecarregar o servidor"



);
