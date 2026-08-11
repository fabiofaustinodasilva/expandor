@include('errors.layout', [
    'title' => 'Erro interno',
    'code' => '500',
    'heading' => 'Algo deu errado',
    'message' => 'Não foi possível concluir esta ação. Tente novamente em instantes. Se persistir, fale com o suporte.',
])
