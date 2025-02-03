jQuery(document).ready(function ($) {
  // Controle de campos de identificação
  $('#identificacao').on('change', function () {
    if ($(this).val() === 'identificado') {
      $('#campos-identificacao').slideDown()
      $('#nome, #email').prop('required', true)
    } else {
      $('#campos-identificacao').slideUp()
      $('#nome, #email').prop('required', false)
    }
  })

  // Submissão do formulário de ouvidoria
  $('#ouvidoria-form').on('submit', function (e) {
    e.preventDefault()

    var $form = $(this)
    var $submit = $form.find('button[type="submit"]')
    var $mensagem = $('#ouvidoria-mensagem')

    $submit.prop('disabled', true)

    var formData = new FormData(this)
    formData.append('action', 'submit_ouvidoria')

    $.ajax({
      url: ouvidoriaPublic.ajax_url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          $mensagem
            .removeClass('erro')
            .addClass('sucesso')
            .html(
              'Solicitação enviada com sucesso! Seu protocolo é: ' +
                response.data.protocolo
            )
            .slideDown()
          $form[0].reset()
        } else {
          $mensagem
            .removeClass('sucesso')
            .addClass('erro')
            .html(response.data)
            .slideDown()
        }
      },
      error: function () {
        $mensagem
          .removeClass('sucesso')
          .addClass('erro')
          .html('Erro ao enviar solicitação. Tente novamente.')
          .slideDown()
      },
      complete: function () {
        $submit.prop('disabled', false)
      }
    })
  })

  // Consulta de protocolo
  $('#consulta-protocolo-form').on('submit', function (e) {
    e.preventDefault()

    var $form = $(this)
    var $submit = $form.find('button[type="submit"]')
    var $resultado = $('#resultado-consulta')

    $submit.prop('disabled', true)

    $.ajax({
      url: ouvidoriaPublic.ajax_url,
      type: 'POST',
      data: {
        action: 'consultar_protocolo',
        nonce: ouvidoriaPublic.nonce,
        protocolo: $('#protocolo').val()
      },
      success: function (response) {
        if (response.success) {
          var solicitacao = response.data.solicitacao
          var html = '<dl>'
          html += '<dt>Protocolo:</dt><dd>' + solicitacao.protocolo + '</dd>'
          html += '<dt>Status:</dt><dd>' + solicitacao.status + '</dd>'
          html += '<dt>Data:</dt><dd>' + solicitacao.data_criacao + '</dd>'

          if (solicitacao.resposta) {
            html += '<dt>Resposta:</dt><dd>' + solicitacao.resposta + '</dd>'
            html +=
              '<dt>Data da Resposta:</dt><dd>' +
              solicitacao.data_resposta +
              '</dd>'
          } else {
            html += '<dt>Situação:</dt><dd>Aguardando resposta</dd>'
          }

          html += '</dl>'

          $('.dados-solicitacao').html(html)
          $resultado.slideDown()
        } else {
          $('.dados-solicitacao').html(
            '<p class="erro">' + response.data + '</p>'
          )
          $resultado.slideDown()
        }
      },
      error: function () {
        $('.dados-solicitacao').html(
          '<p class="erro">Erro ao consultar protocolo. Tente novamente.</p>'
        )
        $resultado.slideDown()
      },
      complete: function () {
        $submit.prop('disabled', false)
      }
    })
  })
})
