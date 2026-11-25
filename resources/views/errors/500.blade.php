@include('errors.standalone', [
    'code' => 500,
    'title' => 'Erro no servidor',
    'heading' => 'Algo deu errado do nosso lado.',
    'message' => 'Não foi possível concluir o pedido agora. Tente de novo em alguns instantes. Se o problema continuar, volte ao início e tente outra vez.',
])
