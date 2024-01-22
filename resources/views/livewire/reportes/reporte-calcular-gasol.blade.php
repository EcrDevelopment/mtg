<div>
    <div class="sm:px-6 w-full pt-12 pb-4">
        <div class="bg-gray-200  px-8 py-4 rounded-xl w-full ">

            <div class=" items-center md:block sm:block">
                <div class="p-2 w-64 my-4 md:w-full">
                    <h2 class="text-indigo-600 font-bold text-3xl">
                        <i class="fa-solid fa-square-poll-vertical fa-xl"></i>
                        &nbsp;REPORTE GENERAL DE GASOLUTION
                    </h2>
                </div>

                <div class="w-full  items-center md:flex md:flex-row md:justify-between ">
                    <div class="flex bg-gray-50 items-center p-2 rounded-md mb-4">
                        <span>Taller: </span>
                        <select wire:model="taller"
                            class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate">
                            <option value="">SELECCIONE</option>
                            @isset($talleres)
                                @foreach ($talleres as $taller)
                                    <option value="{{ $taller }}">{{ $taller }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div class="flex bg-white items-center p-2 rounded-md mb-4">
                        <span>Inspector: </span>
                        <select wire:model="ins"
                            class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate">
                            <option value="">SELECCIONE</option>
                            @isset($inspectores)
                                @foreach ($inspectores as $inspector)
                                    <option value="{{ $inspector }}">{{ $inspector }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex bg-white items-center p-2 w-1/2 md:w-48 rounded-md mb-4 ">
                            <span>Desde: </span>
                            <x-date-picker wire:model="fechaInicio" placeholder="Fecha de inicio"
                                class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate" />
                        </div>
                        <div class="flex bg-white items-center p-2 w-1/2 md:w-48 rounded-md mb-4 ">
                            <span>Hasta: </span>
                            <x-date-picker wire:model="fechaFin" placeholder="Fecha de Fin"
                                class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate" />
                        </div>
                    </div>

                    <button wire:click="calcularReporte"
                        class="bg-green-400 px-6 py-4 w-full md:w-auto rounded-md text-white font-semibold tracking-wide cursor-pointer mb-4">
                        <p class="truncate"> Generar reporte </p>
                    </button>
                </div>
                <div class="w-auto my-4">
                    <x-jet-input-error for="taller" />
                    <x-jet-input-error for="ins" />
                    <x-jet-input-error for="fechaInicio" />
                    <x-jet-input-error for="fechaFin" />
                </div>
                <div class="w-full text-center font-semibold text-gray-100 p-4 mb-4 border rounded-md bg-indigo-400 shadow-lg"
                    wire:loading>
                    CARGANDO <i class="fa-solid fa-spinner animate-spin"></i>
                </div>
            </div>
        </div>

        @if (isset($resultados))
            @if ($resultados->count())

                {{-- @foreach ($resultados->groupBy('taller') as $taller => $detallePorTaller)
                        <div class="mb-8">
                            <h2 class="text-indigo-600 text-xl font-bold mb-4">{{ $taller }}</h2> --}}

                @foreach ($resultados->groupBy('certificador') as $certificador => $detallePorCertificador)
                    <div class="bg-gray-200  px-8 py-4 rounded-xl w-full mt-8">
                        <h2 class="text-indigo-600 text-xl font-bold mb-4"> {{ $certificador }} </h2>
                        <div class="overflow-x-auto m-auto w-full" wire:ignore>
                            <div class="inline-block min-w-full py-2 sm:px-6">
                                <div class="overflow-hidden">
                                    <table
                                        class="min-w-full border text-center text-sm font-light dark:border-neutral-500">
                                        <thead class="border-b font-medium dark:border-neutral-500">
                                            <tr class="bg-indigo-200">
                                                <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">#
                                                </th>
                                                <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">
                                                    Taller
                                                </th>
                                                <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">
                                                    Placa
                                                </th>
                                                <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">
                                                    Serie
                                                </th>
                                                <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">
                                                    Tipo
                                                    Servicio</th>
                                                <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">
                                                    Fecha
                                                </th>
                                                {{--
                                                        <th scope="col"
                                                            class="border-r px-6 py-4 dark:border-neutral-500">Precio
                                                        </th>
                                                        --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($detallePorCertificador as $key => $detalle)
                                                <tr class="border-b dark:border-neutral-500 bg-orange-200">
                                                    <td
                                                        class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                        {{ $key + 1 }}
                                                    </td>
                                                    <td
                                                        class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                        {{ $detalle->taller ?? 'N/A' }}
                                                    </td>
                                                    <td
                                                        class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                        {{ $detalle->placa ?? 'En tramite' }}
                                                    </td>
                                                    <td
                                                        class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                        {{ $detalle->serie ?? 'N/A' }}
                                                    </td>
                                                    <td
                                                        class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                        @if ($detalle->tipoServicio == 1)
                                                            Conversion GNV
                                                        @elseif ($detalle->tipoServicio == 2)
                                                            Anual GNV
                                                        @elseif ($detalle->tipoServicio == 6)
                                                            Desmonte
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td
                                                        class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                        {{ $detalle->fecha ?? 'N/A' }}
                                                    </td>
                                                    {{--
                                                            <td
                                                                class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                                {{ $detalle->precio ?? 'N/A' }}
                                                            </td>
                                                            --}}
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div class="mt-4">
                                        <ul class="grid grid-cols-2 gap-4">
                                            @foreach ($detallePorCertificador->groupBy('tiposervicio') as $tipoServicio => $cant)
                                                @php
                                                    $tipo = is_string($tipoServicio) ? json_decode($tipoServicio) : $tipoServicio;
                                                @endphp
                                                <li
                                                    class="flex items-center justify-between bg-gray-100 p-3 rounded-md shadow">
                                                    <span
                                                        class="text-blue-400">{{ 'Cantidad de ' . $tipo->descripcion }}</span>
                                                    <span class="text-green-500">{{ $cant->count() }} servicios</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                {{-- @endforeach --}}
            @else
                <div class="w-full text-center font-semibold text-gray-100 p-4 mb-4 border rounded-md bg-indigo-400 shadow-lg"
                    wire:loading.class="hidden">
                    No se encontraron resultados.
                </div>
            @endif
        @endif



    </div>
</div>
