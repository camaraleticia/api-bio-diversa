#!/bin/bash

# Script para criar imagens de teste simuladas
mkdir -p /home/ubuntu/src/public/uploads/test_images

# Criar imagens de teste para cada espécie
echo "Criando imagem de teste para Quero-quero" > /home/ubuntu/src/public/uploads/test_images/quero_quero_test.jpg
echo "Criando imagem de teste para Capivara" > /home/ubuntu/src/public/uploads/test_images/capivara_test.jpg
echo "Criando imagem de teste para Graxaim" > /home/ubuntu/src/public/uploads/test_images/graxaim_test.jpg
echo "Criando imagem de teste para Babosa-do-campo" > /home/ubuntu/src/public/uploads/test_images/babosa_test.jpg
echo "Criando imagem de teste para Trevo-nativo" > /home/ubuntu/src/public/uploads/test_images/trevo_test.jpg
echo "Criando imagem de teste genérica (baixa confiança)" > /home/ubuntu/src/public/uploads/test_images/generic_test.jpg

echo "Imagens de teste criadas com sucesso!"
