<?php

namespace Tests\Unit;

use App\Enums\BrazilianState;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrazilianStateTest extends TestCase
{
    #[Test]
    public function it_has_all_brazilian_states()
    {
        $expectedStates = [
            'ACRE' => 'AC',
            'ALAGOAS' => 'AL',
            'AMAPA' => 'AP',
            'AMAZONAS' => 'AM',
            'BAHIA' => 'BA',
            'CEARA' => 'CE',
            'DISTRITO_FEDERAL' => 'DF',
            'ESPIRITO_SANTO' => 'ES',
            'GOIAS' => 'GO',
            'MARANHAO' => 'MA',
            'MATO_GROSSO' => 'MT',
            'MATO_GROSSO_DO_SUL' => 'MS',
            'MINAS_GERAIS' => 'MG',
            'PARA' => 'PA',
            'PARAIBA' => 'PB',
            'PARANA' => 'PR',
            'PERNAMBUCO' => 'PE',
            'PIAUI' => 'PI',
            'RIO_DE_JANEIRO' => 'RJ',
            'RIO_GRANDE_DO_NORTE' => 'RN',
            'RIO_GRANDE_DO_SUL' => 'RS',
            'RONDONIA' => 'RO',
            'RORAIMA' => 'RR',
            'SANTA_CATARINA' => 'SC',
            'SAO_PAULO' => 'SP',
            'SERGIPE' => 'SE',
            'TOCANTINS' => 'TO',
        ];

        $cases = BrazilianState::cases();

        $this->assertCount(27, $cases, 'Deve ter exatamente 27 estados brasileiros');

        foreach ($expectedStates as $name => $value) {
            $case = constant("App\Enums\BrazilianState::{$name}");
            $this->assertEquals($value, $case->value, "Estado {$name} deve ter valor {$value}");
        }
    }

    #[Test]
    public function it_can_be_instantiated_from_string_value()
    {
        $state = BrazilianState::from('SP');
        $this->assertEquals(BrazilianState::SAO_PAULO, $state);

        $state = BrazilianState::from('RJ');
        $this->assertEquals(BrazilianState::RIO_DE_JANEIRO, $state);

        $state = BrazilianState::from('MG');
        $this->assertEquals(BrazilianState::MINAS_GERAIS, $state);
    }

    #[Test]
    public function it_throws_exception_for_invalid_state_code()
    {
        $this->expectException(\ValueError::class);

        BrazilianState::from('XY');
    }

    #[Test]
    public function it_can_try_to_instantiate_from_string_value()
    {
        $state = BrazilianState::tryFrom('SP');
        $this->assertEquals(BrazilianState::SAO_PAULO, $state);

        $state = BrazilianState::tryFrom('INVALID');
        $this->assertNull($state);
    }

    #[Test]
    public function it_returns_correct_string_values()
    {
        $this->assertEquals('SP', BrazilianState::SAO_PAULO->value);
        $this->assertEquals('RJ', BrazilianState::RIO_DE_JANEIRO->value);
        $this->assertEquals('MG', BrazilianState::MINAS_GERAIS->value);
        $this->assertEquals('AC', BrazilianState::ACRE->value);
        $this->assertEquals('DF', BrazilianState::DISTRITO_FEDERAL->value);
    }

    #[Test]
    public function it_returns_correct_names()
    {
        $this->assertEquals('SAO_PAULO', BrazilianState::SAO_PAULO->name);
        $this->assertEquals('RIO_DE_JANEIRO', BrazilianState::RIO_DE_JANEIRO->name);
        $this->assertEquals('MINAS_GERAIS', BrazilianState::MINAS_GERAIS->name);
        $this->assertEquals('DISTRITO_FEDERAL', BrazilianState::DISTRITO_FEDERAL->name);
    }

    #[Test]
    public function it_gets_all_values_as_array()
    {
        $values = BrazilianState::getValues();

        $this->assertIsArray($values);
        $this->assertCount(27, $values);

        // Verificar se contém alguns estados específicos
        $this->assertContains('SP', $values);
        $this->assertContains('RJ', $values);
        $this->assertContains('MG', $values);
        $this->assertContains('AC', $values);
        $this->assertContains('DF', $values);

        // Verificar se todos os valores são strings de 2 caracteres
        foreach ($values as $value) {
            $this->assertIsString($value);
            $this->assertEquals(2, strlen($value));
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $value);
        }
    }

    #[Test]
    public function it_gets_all_names_as_array()
    {
        $names = BrazilianState::getNames();

        $this->assertIsArray($names);
        $this->assertCount(27, $names);

        // Verificar se contém alguns nomes específicos
        $this->assertContains('SAO_PAULO', $names);
        $this->assertContains('RIO_DE_JANEIRO', $names);
        $this->assertContains('MINAS_GERAIS', $names);
        $this->assertContains('ACRE', $names);
        $this->assertContains('DISTRITO_FEDERAL', $names);

        // Verificar se todos os nomes são strings em maiúsculo
        foreach ($names as $name) {
            $this->assertIsString($name);
            $this->assertMatchesRegularExpression('/^[A-Z_]+$/', $name);
        }
    }

    #[Test]
    public function it_has_unique_values()
    {
        $values = BrazilianState::getValues();
        $uniqueValues = array_unique($values);

        $this->assertEquals(count($values), count($uniqueValues), 'Todos os valores devem ser únicos');
    }

    #[Test]
    public function it_has_unique_names()
    {
        $names = BrazilianState::getNames();
        $uniqueNames = array_unique($names);

        $this->assertEquals(count($names), count($uniqueNames), 'Todos os nomes devem ser únicos');
    }

    #[Test]
    public function it_can_be_used_in_switch_statements()
    {
        $state = BrazilianState::SAO_PAULO;

        $region = match ($state) {
            BrazilianState::SAO_PAULO, BrazilianState::RIO_DE_JANEIRO, BrazilianState::MINAS_GERAIS, BrazilianState::ESPIRITO_SANTO => 'Sudeste',
            BrazilianState::RIO_GRANDE_DO_SUL, BrazilianState::SANTA_CATARINA, BrazilianState::PARANA => 'Sul',
            BrazilianState::BAHIA, BrazilianState::SERGIPE, BrazilianState::ALAGOAS, BrazilianState::PERNAMBUCO, BrazilianState::PARAIBA, BrazilianState::RIO_GRANDE_DO_NORTE, BrazilianState::CEARA, BrazilianState::PIAUI, BrazilianState::MARANHAO => 'Nordeste',
            BrazilianState::ACRE, BrazilianState::AMAZONAS, BrazilianState::RORAIMA, BrazilianState::RONDONIA, BrazilianState::PARA, BrazilianState::AMAPA, BrazilianState::TOCANTINS => 'Norte',
            BrazilianState::MATO_GROSSO, BrazilianState::MATO_GROSSO_DO_SUL, BrazilianState::GOIAS, BrazilianState::DISTRITO_FEDERAL => 'Centro-Oeste',
        };

        $this->assertEquals('Sudeste', $region);
    }

    #[Test]
    public function it_can_be_compared()
    {
        $sp1 = BrazilianState::SAO_PAULO;
        $sp2 = BrazilianState::from('SP');
        $rj = BrazilianState::RIO_DE_JANEIRO;

        $this->assertTrue($sp1 === $sp2);
        $this->assertFalse($sp1 === $rj);
        $this->assertTrue($sp1->value === $sp2->value);
    }

    #[Test]
    public function it_can_be_serialized_and_unserialized()
    {
        $state = BrazilianState::SAO_PAULO;

        $serialized = serialize($state);
        $unserialized = unserialize($serialized);

        $this->assertEquals($state, $unserialized);
        $this->assertEquals($state->value, $unserialized->value);
        $this->assertEquals($state->name, $unserialized->name);
    }

    #[Test]
    public function it_can_be_json_encoded()
    {
        $state = BrazilianState::SAO_PAULO;

        $json = json_encode($state);
        $this->assertEquals('"SP"', $json);

        $decoded = json_decode($json, true);
        $this->assertEquals('SP', $decoded);
    }

    #[Test]
    public function it_works_with_array_functions()
    {
        $states = [
            BrazilianState::SAO_PAULO,
            BrazilianState::RIO_DE_JANEIRO,
            BrazilianState::MINAS_GERAIS,
        ];

        $this->assertTrue(in_array(BrazilianState::SAO_PAULO, $states));
        $this->assertFalse(in_array(BrazilianState::BAHIA, $states));

        $values = array_map(fn ($state) => $state->value, $states);
        $this->assertEquals(['SP', 'RJ', 'MG'], $values);
    }

    #[Test]
    public function it_validates_all_required_states_exist()
    {
        // Teste para garantir que todos os 26 estados + DF estão presentes
        $requiredStates = [
            'AC',
            'AL',
            'AP',
            'AM',
            'BA',
            'CE',
            'DF',
            'ES',
            'GO',
            'MA',
            'MT',
            'MS',
            'MG',
            'PA',
            'PB',
            'PR',
            'PE',
            'PI',
            'RJ',
            'RN',
            'RS',
            'RO',
            'RR',
            'SC',
            'SP',
            'SE',
            'TO',
        ];

        $actualValues = BrazilianState::getValues();

        foreach ($requiredStates as $requiredState) {
            $this->assertContains($requiredState, $actualValues, "Estado {$requiredState} não encontrado");
        }

        $this->assertCount(27, $actualValues, 'Deve ter exatamente 27 estados (26 estados + DF)');
    }

    #[Test]
    public function it_implements_backed_enum_interface()
    {
        $state = BrazilianState::SAO_PAULO;

        $this->assertInstanceOf(\BackedEnum::class, $state);
        $this->assertInstanceOf(\UnitEnum::class, $state);
    }
}
