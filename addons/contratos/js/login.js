function applyCpfCnpjMask(input) {
  // Remove todos os caracteres que não são dígitos
  let value = input.value.replace(/\D/g, "");

  // Limita o valor a no máximo 14 dígitos (CNPJ)
  if (value.length > 14) {
    value = value.slice(0, 14);
  }

  // Aplica a máscara para CPF ou CNPJ
  if (value.length <= 11) {
    input.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
  } else {
    input.value = value.replace(
      /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
      "$1.$2.$3/$4-$5"
    );
  }
}
