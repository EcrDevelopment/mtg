<div>
    <div class="sm:px-6 w-full pt-12 pb-4">
        <div class="bg-gray-200  px-8 py-4 rounded-xl w-full ">

            <div class=" items-center md:block sm:block">
                <div class="p-2 w-64 my-4 md:w-full">
                    <h2 class="text-indigo-600 font-bold text-3xl">
                        <i class="fa-solid fa-square-poll-vertical fa-xl"></i>
                        &nbsp;REPORTE GENERAL DE SERVICIOS MOTORGAS-COMPANY
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
            @forelse ($resultados->groupBy('idTaller') as $taller => $certificacionesTaller)
                <div class="flex flex-col my-4 py-4 rounded-md bg-white px-4 justify-center">
                    <h2 class="text-indigo-600 text-xl font-bold mb-4">{{ $certificacionesTaller[0]->taller }}</h2>
                    
                    @foreach ($certificacionesTaller->groupBy('idInspector') as $inspector => $certificacionesInspector)
                        <h3 class="text-red-600  font-bold mb-2">{{ '✔️ ' . $certificacionesInspector[0]->nombre }}</h3>
                        @if ($certificacionesInspector->count() > 0)
                            <div class="overflow-x-auto m-auto w-full" wire:ignore>
                                <div class="inline-block min-w-full py-2 sm:px-6">
                                    <div class="overflow-hidden">
                                        <table
                                            class="min-w-full border text-center text-sm font-light dark:border-neutral-500">
                                            <thead class="border-b font-medium dark:border-neutral-500">
                                                <tr class="bg-indigo-200">
                                                    <th scope="col"
                                                        class="border-r px-6 py-4 dark:border-neutral-500">#
                                                    </th>
                                                    <th scope="col"
                                                        class="border-r px-6 py-4 dark:border-neutral-500">
                                                        Inspector</th>
                                                    <th scope="col"
                                                        class="border-r px-6 py-4 dark:border-neutral-500">
                                                        Vehículo</th>
                                                    <th scope="col"
                                                        class="border-r px-6 py-4 dark:border-neutral-500">
                                                        Servicio</th>
                                                    <th scope="col"
                                                        class="border-r px-6 py-4 dark:border-neutral-500">
                                                        Fecha
                                                    </th>
                                                    <th scope="col"
                                                        class="border-r px-6 py-4 dark:border-neutral-500">
                                                        Estado
                                                    </th>
                                                    <th scope="col"
                                                        class="border-r px-6 py-4 dark:border-neutral-500">
                                                        Precio
                                                    </th>
                                                    <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">
                                                        <input type="checkbox" wire:model="selectAll">
                                                        Seleccionar Todo
                                                    </th>                                                    
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($certificacionesInspector as $key => $item)
                                                    <tr class="border-b dark:border-neutral-500 bg-orange-200">
                                                        <td
                                                            class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                            {{ $key + 1 }}</td>

                                                        <td
                                                            class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                            @if (is_array($item))
                                                                {{ $item['totalPrecio'] }}
                                                            @else
                                                                {{ $item->nombre }}
                                                            @endif
                                                        </td>
                                                        <td
                                                            class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                            {{ $item->placa ?? 'En tramite' }}</td>
                                                        <td
                                                            class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                            {{ $item->tiposervicio }}</td>
                                                        <td
                                                            class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                                                        </td>                                                        
                                                        <td
                                                            class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                            @if ($item->pagado == 0)
                                                                Sin cobrar
                                                            @elseif ($detalle->tipoServicio == 1)
                                                                Cobrado
                                                            @else
                                                                N/A
                                                            @endif
                                                        </td>
                                                        <td
                                                            class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                            {{ $item->precio }}
                                                        </td>
                                                        <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                            <input type="checkbox" wire:model="selectedItems" value="{{ $item->id }}">
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                <tr class="border-b dark:border-neutral-500 bg-green-200">
                                                    <td colspan="6"
                                                        class="border-r px-6 py-3 dark:border-neutral-500 font-bold text-right">
                                                        Total: {{-- ({{ $certificacionesInspector[0]->nombre }}) --}}
                                                    </td>
                                                    <td class="border-r px-6 py-3 dark:border-neutral-500 font-bold">
                                                        {{ number_format($certificacionesInspector->sum('precio'), 2) }}
                                                    </td>
                                                    <td >
                                                        <div class="flex justify-center  space-x-2">
                                                            <a 
                                                            wire:click=""
                                                            class="group flex py-2 px-2 text-center rounded-md bg-blue-300 font-bold text-white cursor-pointer hover:bg-blue-400 hover:animate-pulse" >
                                                                <i class="fas fa-edit"></i>
                                                                <span class="group-hover:opacity-100 transition-opacity bg-gray-800 px-1 text-sm text-gray-100 rounded-md absolute left-1/2-translate-x-1/2 translate-y-full opacity-0 m-4 mx-auto">
                                                                    Actualizar
                                                                </span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="mt-4">
                                            <ul class="grid grid-cols-2 gap-4">
                                                @foreach ($certificacionesInspector->groupBy('tiposervicio') as $tipoServicio => $detalle)
                                                    <li
                                                        class="flex items-center justify-between bg-gray-100 p-3 rounded-md shadow">
                                                        <span
                                                            class="text-blue-400">{{ 'Cantidad de ' . $tipoServicio }}</span>
                                                        <span class="text-green-500">{{ $detalle->count() }}
                                                            servicios</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-center text-gray-500">No hay certificaciones para este taller.</p>
                        @endif
                    @endforeach
                </div>
            @empty
                <div class="w-full text-center font-semibold text-gray-100 p-4 mb-4 border rounded-md bg-indigo-400 shadow-lg"
                    wire:loading.class="hidden">
                    No se encontraron resultados.
                </div>
            @endforelse
        @endif
    </div>
</div>
