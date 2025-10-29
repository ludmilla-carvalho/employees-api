<?php

namespace App\Enums;

enum BrazilianState: string
{
    case ACRE = 'AC';
    case ALAGOAS = 'AL';
    case AMAPA = 'AP';
    case AMAZONAS = 'AM';
    case BAHIA = 'BA';
    case CEARA = 'CE';
    case DISTRITO_FEDERAL = 'DF';
    case ESPIRITO_SANTO = 'ES';
    case GOIAS = 'GO';
    case MARANHAO = 'MA';
    case MATO_GROSSO = 'MT';
    case MATO_GROSSO_DO_SUL = 'MS';
    case MINAS_GERAIS = 'MG';
    case PARA = 'PA';
    case PARAIBA = 'PB';
    case PARANA = 'PR';
    case PERNAMBUCO = 'PE';
    case PIAUI = 'PI';
    case RIO_DE_JANEIRO = 'RJ';
    case RIO_GRANDE_DO_NORTE = 'RN';
    case RIO_GRANDE_DO_SUL = 'RS';
    case RONDONIA = 'RO';
    case RORAIMA = 'RR';
    case SANTA_CATARINA = 'SC';
    case SAO_PAULO = 'SP';
    case SERGIPE = 'SE';
    case TOCANTINS = 'TO';

    /**
     * Retorna o nome completo do estado
     */
    public function getFullName(): string
    {
        return match ($this) {
            self::ACRE => 'Acre',
            self::ALAGOAS => 'Alagoas',
            self::AMAPA => 'Amapá',
            self::AMAZONAS => 'Amazonas',
            self::BAHIA => 'Bahia',
            self::CEARA => 'Ceará',
            self::DISTRITO_FEDERAL => 'Distrito Federal',
            self::ESPIRITO_SANTO => 'Espírito Santo',
            self::GOIAS => 'Goiás',
            self::MARANHAO => 'Maranhão',
            self::MATO_GROSSO => 'Mato Grosso',
            self::MATO_GROSSO_DO_SUL => 'Mato Grosso do Sul',
            self::MINAS_GERAIS => 'Minas Gerais',
            self::PARA => 'Pará',
            self::PARAIBA => 'Paraíba',
            self::PARANA => 'Paraná',
            self::PERNAMBUCO => 'Pernambuco',
            self::PIAUI => 'Piauí',
            self::RIO_DE_JANEIRO => 'Rio de Janeiro',
            self::RIO_GRANDE_DO_NORTE => 'Rio Grande do Norte',
            self::RIO_GRANDE_DO_SUL => 'Rio Grande do Sul',
            self::RONDONIA => 'Rondônia',
            self::RORAIMA => 'Roraima',
            self::SANTA_CATARINA => 'Santa Catarina',
            self::SAO_PAULO => 'São Paulo',
            self::SERGIPE => 'Sergipe',
            self::TOCANTINS => 'Tocantins',
        };
    }

    /**
     * Retorna a região do estado
     */
    public function getRegion(): string
    {
        return match ($this) {
            self::ACRE, self::AMAPA, self::AMAZONAS, self::PARA, self::RONDONIA, self::RORAIMA, self::TOCANTINS => 'Norte',
            self::ALAGOAS, self::BAHIA, self::CEARA, self::MARANHAO, self::PARAIBA, self::PERNAMBUCO, self::PIAUI, self::RIO_GRANDE_DO_NORTE, self::SERGIPE => 'Nordeste',
            self::DISTRITO_FEDERAL, self::GOIAS, self::MATO_GROSSO, self::MATO_GROSSO_DO_SUL => 'Centro-Oeste',
            self::ESPIRITO_SANTO, self::MINAS_GERAIS, self::RIO_DE_JANEIRO, self::SAO_PAULO => 'Sudeste',
            self::PARANA, self::RIO_GRANDE_DO_SUL, self::SANTA_CATARINA => 'Sul',
        };
    }

    /**
     * Retorna todos os valores possíveis como array
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna todos os estados de uma região específica
     */
    public static function getByRegion(string $region): array
    {
        return array_filter(self::cases(), fn ($state) => $state->getRegion() === $region);
    }
}
