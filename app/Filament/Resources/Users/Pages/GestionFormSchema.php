          ->afterStateUpdated(function ($state, $get, $set) {
                        $inicio = (int)$state;
                        if ($inicio > 0) {
                            $fin = $inicio + 3;
                            $set('end_year', $fin);
                            $set('name', "Gestión {$inicio}-{$fin}");
                        }
                    }),
