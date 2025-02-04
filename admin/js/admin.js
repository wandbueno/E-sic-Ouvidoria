jQuery(document).ready(function ($) {
  console.log('Script da Ouvidoria carregado')

  // Controle de campos de identificação
  $('#identificacao').on('change', function () {
    var valor = $(this).val()
    console.log('Valor selecionado:', valor)

    if (valor === 'identificado') {
      $('.campo-identificacao').show()
      $('#nome, #email').prop('required', true)
    } else {
      $('.campo-identificacao').hide()
      $('#nome, #email').prop('required', false)
    }
  })

  // Submissão do formulário de nova solicitação
  $('#form-nova-solicitacao').on('submit', function (e) {
    e.preventDefault()

    var $form = $(this)
    var $submitButton = $form.find('button[type="submit"]')
    var $mensagem = $('#mensagem-resposta')

    // Desabilita o botão e mostra feedback
    $submitButton.prop('disabled', true)
    $submitButton.html('<span class="dashicons dashicons-update-alt spin"></span> Enviando...')

    var formData = new FormData(this)
    formData.append('action', 'salvar_solicitacao')
    formData.append('nonce', $('#nova_nonce').val())

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log('Resposta:', response)
        if (response.success) {
          alert('Solicitação salva com sucesso!\nProtocolo: ' + response.data.protocolo)
          // Recarrega a página após o alerta
          window.location.reload()
        } else {
          $mensagem
            .removeClass('notice-success')
            .addClass('notice notice-error')
            .html('<p>Erro ao salvar: ' + (response.data || 'Erro desconhecido') + '</p>')
            .slideDown()
        }
      },
      error: function (xhr, status, error) {
        console.log('Erro:', error)
        $mensagem
          .removeClass('notice-success')
          .addClass('notice notice-error')
          .html('<p>Erro ao salvar solicitação: ' + error + '</p>')
          .slideDown()
      },
      complete: function () {
        // Restaura o botão
        $submitButton.prop('disabled', false)
        $submitButton.html('Criar Solicitação')
      }
    })
  })

  // Submissão do formulário de resposta
  $('#form-resposta').on('submit', function (e) {
    e.preventDefault()
    console.log('Formulário de resposta submetido')

    var $form = $(this)
    var $submitButton = $form.find('button[type="submit"]')
    var $mensagem = $('#mensagem-resposta')

    // Desabilita o botão para evitar duplo envio
    $submitButton.prop('disabled', true)
    $submitButton.html('<span class="dashicons dashicons-update-alt spin"></span> Enviando...')

    var formData = new FormData(this)
    formData.append('action', 'adicionar_resposta')
    formData.append('nonce', $('#nova_nonce').val())

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log('Resposta:', response)
        if (response.success) {
          // Mostra mensagem de sucesso
          $mensagem
            .removeClass('notice-error')
            .addClass('notice notice-success')
            .html('<p>Resposta enviada com sucesso!</p>')
            .slideDown()
          
          // Recarrega a página após 1 segundo
          setTimeout(function() {
            location.reload()
          }, 1000)
        } else {
          $mensagem
            .removeClass('notice-success')
            .addClass('notice notice-error')
            .html('<p>Erro ao enviar resposta: ' + (response.data || 'Erro desconhecido') + '</p>')
            .slideDown()
        }
      },
      error: function (xhr, status, error) {
        console.log('Erro:', error)
        $mensagem
          .removeClass('notice-success')
          .addClass('notice notice-error')
          .html('<p>Erro ao enviar resposta: ' + error + '</p>')
          .slideDown()
      },
      complete: function () {
        // Reabilita o botão
        $submitButton.prop('disabled', false)
        $submitButton.html('Enviar Resposta')
      }
    })
  })
})